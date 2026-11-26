import { computePeaks, drawWave } from './waveform';

/**
 * Importeur du studio : glisser-déposer de fichiers audio, pré-remplissage
 * (titre, BPM, tonalité, catégorie devinés depuis le nom), analyse dans le
 * navigateur (durée, forme d'onde, aperçu 22 kHz mono de 30 s max.), puis
 * envoi fichier par fichier avec progression.
 */
const PREVIEW_RATE = 22050;
const MAX_ANALYSE_BYTES = 160 * 1024 * 1024;

const CATEGORY_HINTS = {
    'female vocals': ['female', 'fem', 'girl', 'woman', 'femme', 'feminine'],
    'male vocals': ['male', 'man', 'guy', 'homme', 'masculine'],
    'vocal chops': ['chop', 'chops'],
    'adlibs': ['adlib', 'adlibs', 'ad-lib'],
    'choirs & harmonies': ['choir', 'harmony', 'harmonies'],
    'spoken word': ['spoken', 'speech', 'talk'],
    'kicks': ['kick', 'kik', 'bd', 'bassdrum'],
    'snares': ['snare', 'snr', 'sd'],
    'claps': ['clap', 'claps'],
    'open hats': ['openhat', 'oh', 'open'],
    'hi-hats': ['hat', 'hats', 'hihat', 'hh', 'hi-hat'],
    'cymbals & crashes': ['crash', 'ride', 'cymbal'],
    'percussion': ['perc', 'percussion', 'shaker', 'conga', 'bongo', 'tom', 'rim'],
    'drum loops': ['drumloop', 'drums', 'beat', 'groove'],
    'fills': ['fill', 'fills', 'roll'],
    '808s': ['808', '808s'],
    'sub bass': ['sub', 'subbass'],
    'synth bass': ['synthbass', 'reese', 'wobble'],
    'bass loops': ['bassloop', 'bassline'],
    'leads': ['lead', 'leads'],
    'pads': ['pad', 'pads'],
    'plucks': ['pluck', 'plucks'],
    'arps': ['arp', 'arps', 'arpeggio'],
    'chords': ['chord', 'chords', 'stab'],
    'keys & piano': ['piano', 'keys', 'rhodes', 'organ', 'ep'],
    'guitars': ['guitar', 'gtr'],
    'strings': ['string', 'strings', 'violin', 'cello'],
    'brass & winds': ['brass', 'horn', 'trumpet', 'sax', 'flute'],
    'risers & sweeps': ['riser', 'sweep', 'uplifter', 'build'],
    'impacts & downlifters': ['impact', 'hit', 'downlifter', 'boom'],
    'textures & ambiences': ['texture', 'ambience', 'ambient', 'atmos', 'drone'],
    'foley': ['foley', 'fx', 'sfx', 'noise'],
};

const KEY_PATTERN = /(?:^|[\s_\-()[\]])([A-G])(#|b|♯|♭)?\s?(m|min|minor|maj|major)?(?=$|[\s_\-()[\]])/i;

export function initUploader() {
    const root = document.querySelector('[data-uploader]');

    if (!root) {
        return;
    }

    const input = root.querySelector('[data-files]');
    const dropzone = root.querySelector('[data-dropzone]');
    const list = root.querySelector('[data-list]');
    const template = root.querySelector('template[data-row]');
    const startButton = root.querySelector('[data-start]');
    const summary = root.querySelector('[data-summary]');
    const defaults = {
        pack: root.querySelector('[name="default_pack"]'),
        category: root.querySelector('[name="default_category"]'),
        type: root.querySelector('[name="default_type"]'),
        price: root.querySelector('[name="default_price"]'),
        tags: root.querySelector('[name="default_tags"]'),
        publish: root.querySelector('[name="default_publish"]'),
    };
    const maxFiles = Number(root.dataset.maxFiles || 50);
    const formats = (root.dataset.formats || '').split(',');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const items = [];
    let audioContext = null;

    const categoryOptions = [...template.content.querySelectorAll('[data-field="category_id"] option')]
        .filter((o) => o.value)
        .map((o) => ({ value: o.value, label: o.textContent.trim().toLowerCase() }));

    function guessCategory(name) {
        const tokens = name.toLowerCase().replace(/[^a-z0-9#]+/g, ' ').split(' ').filter(Boolean);
        const joined = tokens.join('');

        for (const [label, words] of Object.entries(CATEGORY_HINTS)) {
            if (words.some((w) => tokens.includes(w) || (w.length > 4 && joined.includes(w)))) {
                const option = categoryOptions.find((o) => o.label === label);

                if (option) {
                    return option.value;
                }
            }
        }

        if (tokens.some((t) => ['vox', 'vocal', 'vocals', 'voice', 'acapella', 'acappella'].includes(t))) {
            return categoryOptions.find((o) => o.label === 'vocal chops')?.value ?? '';
        }

        return '';
    }

    function parseName(fileName) {
        const base = fileName.replace(/\.[^.]+$/, '');
        const bpmMatch = base.match(/(\d{2,3})\s?bpm/i) || base.match(/(?:^|[_\-\s])(\d{2,3})(?=$|[_\-\s])/);
        const bpm = bpmMatch ? Number(bpmMatch[1]) : null;
        const keyMatch = base.match(KEY_PATTERN);
        let key = '';

        if (keyMatch) {
            const flats = { Db: 'C#', Eb: 'D#', Gb: 'F#', Ab: 'G#', Bb: 'A#' };
            let note = keyMatch[1].toUpperCase() + (keyMatch[2] ? keyMatch[2].replace('♯', '#').replace('♭', 'b') : '');
            note = flats[note] || note.replace(/b$/, '');
            const minor = /^m(in(or)?)?$/i.test(keyMatch[3] || '');
            key = note + (minor ? 'm' : '');
        }

        const title = base
            .replace(/(\d{2,3})\s?bpm/ig, '')
            .replace(/[_]+/g, ' ')
            .replace(/\s{2,}/g, ' ')
            .trim();

        return {
            title: title.charAt(0).toUpperCase() + title.slice(1),
            bpm: bpm && bpm >= 40 && bpm <= 300 ? bpm : null,
            key,
            loop: /loop/i.test(base) || (bpm !== null && bpm >= 40 && bpm <= 300),
        };
    }

    function addFiles(files) {
        for (const file of files) {
            if (items.length >= maxFiles) {
                updateSummary(`Maximum ${maxFiles} fichiers par import.`);
                break;
            }

            const extension = file.name.split('.').pop().toLowerCase();

            if (!formats.includes(extension)) {
                updateSummary(`« ${file.name} » ignoré : format non accepté.`);
                continue;
            }

            items.push(createItem(file));
        }

        updateSummary();
    }

    function createItem(file) {
        const node = template.content.firstElementChild.cloneNode(true);
        const field = (name) => node.querySelector(`[data-field="${name}"]`);
        const parsed = parseName(file.name);
        const item = { file, node, field, preview: null, peaks: null, durationMs: null, status: 'pending', previewUrl: null };

        node.querySelector('[data-name]').textContent = file.name;
        node.querySelector('[data-size]').textContent = `${(file.size / 1048576).toFixed(1)} Mo`;
        field('title').value = parsed.title.slice(0, 120);
        field('bpm').value = parsed.bpm ?? '';
        field('musical_key').value = parsed.key;
        field('type').value = parsed.loop ? 'loop' : (defaults.type?.value || 'one_shot');
        field('pack_id').value = defaults.pack?.value || '';
        field('category_id').value = guessCategory(file.name) || defaults.category?.value || '';
        field('price').value = defaults.price?.value || '1.99';
        field('tags').value = defaults.tags?.value || '';

        node.querySelector('[data-remove]').addEventListener('click', () => {
            if (item.status === 'uploading') {
                return;
            }

            items.splice(items.indexOf(item), 1);
            node.remove();
            updateSummary();
        });

        node.querySelector('[data-preview]').addEventListener('click', (event) => {
            const button = event.currentTarget;

            if (!item.previewUrl) {
                return;
            }

            if (!item.audio) {
                item.audio = new Audio(item.previewUrl);
                item.audio.addEventListener('ended', () => button.classList.remove('is-playing'));
            }

            if (item.audio.paused) {
                document.querySelectorAll('[data-uploader] [data-preview].is-playing').forEach((b) => b.click());
                item.audio.currentTime = 0;
                item.audio.play();
                button.classList.add('is-playing');
            } else {
                item.audio.pause();
                button.classList.remove('is-playing');
            }
        });

        list.appendChild(node);
        analyse(item);

        return item;
    }

    async function analyse(item) {
        const status = item.node.querySelector('[data-status]');
        const canvas = item.node.querySelector('canvas');

        if (item.file.size > MAX_ANALYSE_BYTES) {
            status.textContent = 'Fichier volumineux : aperçu à ajouter après import.';
            drawWave(canvas, [], 0);

            return;
        }

        status.textContent = 'Analyse…';

        try {
            audioContext ??= new (window.AudioContext || window.webkitAudioContext)();
            const buffer = await audioContext.decodeAudioData(await item.file.arrayBuffer());
            item.durationMs = Math.round(buffer.duration * 1000);
            item.peaks = computePeaks(buffer, 120);
            item.preview = await renderPreview(buffer);
            item.previewUrl = URL.createObjectURL(item.preview);
            item.node.querySelector('[data-preview]').disabled = false;
            drawWave(canvas, item.peaks, 0);
            status.textContent = `${buffer.duration.toFixed(2)} s · ${buffer.sampleRate / 1000} kHz · aperçu prêt`;

            if (item.durationMs > 4000 && !item.field('bpm').value) {
                item.field('type').value = item.field('type').value || 'loop';
            }
        } catch {
            status.textContent = 'Analyse impossible dans ce navigateur : le son sera importé sans aperçu.';
            drawWave(canvas, [], 0);
        }
    }

    async function renderPreview(buffer) {
        const seconds = Math.min(buffer.duration, Number(root.dataset.previewSeconds || 30));
        const frames = Math.max(1, Math.ceil(seconds * PREVIEW_RATE));
        const offline = new OfflineAudioContext(1, frames, PREVIEW_RATE);
        const source = offline.createBufferSource();
        const gain = offline.createGain();
        source.buffer = buffer;
        source.connect(gain).connect(offline.destination);

        if (buffer.duration > seconds) {
            gain.gain.setValueAtTime(1, Math.max(0, seconds - 1.5));
            gain.gain.linearRampToValueAtTime(0, seconds);
        }

        source.start(0);
        const rendered = await offline.startRendering();

        return encodeWav(rendered.getChannelData(0), PREVIEW_RATE);
    }

    function encodeWav(samples, rate) {
        const bytes = new ArrayBuffer(44 + samples.length * 2);
        const view = new DataView(bytes);
        const text = (offset, value) => [...value].forEach((c, i) => view.setUint8(offset + i, c.charCodeAt(0)));

        text(0, 'RIFF');
        view.setUint32(4, 36 + samples.length * 2, true);
        text(8, 'WAVE');
        text(12, 'fmt ');
        view.setUint32(16, 16, true);
        view.setUint16(20, 1, true);
        view.setUint16(22, 1, true);
        view.setUint32(24, rate, true);
        view.setUint32(28, rate * 2, true);
        view.setUint16(32, 2, true);
        view.setUint16(34, 16, true);
        text(36, 'data');
        view.setUint32(40, samples.length * 2, true);

        for (let i = 0; i < samples.length; i++) {
            const s = Math.max(-1, Math.min(1, samples[i]));
            view.setInt16(44 + i * 2, s < 0 ? s * 0x8000 : s * 0x7fff, true);
        }

        return new Blob([bytes], { type: 'audio/wav' });
    }

    function upload(item) {
        return new Promise((resolve) => {
            const data = new FormData();
            const bar = item.node.querySelector('.progress span');
            const status = item.node.querySelector('[data-status]');

            data.append('file', item.file);

            if (item.preview) {
                data.append('preview', item.preview, 'preview.wav');
            }

            if (item.peaks) {
                data.append('waveform', JSON.stringify(item.peaks));
            }

            if (item.durationMs) {
                data.append('duration_ms', item.durationMs);
            }

            ['title', 'category_id', 'pack_id', 'type', 'bpm', 'musical_key', 'tags', 'price'].forEach((name) => {
                data.append(name, item.field(name).value);
            });
            data.append('publish', defaults.publish?.checked ? '1' : '0');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', root.dataset.storeUrl);
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) {
                    bar.style.width = `${Math.round((event.loaded / event.total) * 100)}%`;
                }
            });
            xhr.addEventListener('load', () => {
                let body = {};

                try {
                    body = JSON.parse(xhr.responseText);
                } catch {
                    // Réponse non JSON.
                }

                if (xhr.status === 201) {
                    item.status = 'done';
                    item.node.classList.add('is-done');
                    bar.style.width = '100%';
                    status.innerHTML = body.status === 'published'
                        ? `Publié ✓ — <a href="${body.edit}">modifier</a>`
                        : `Enregistré en brouillon ✓ — <a href="${body.edit}">compléter</a>`;
                    item.node.querySelectorAll('input, select').forEach((el) => { el.disabled = true; });
                } else {
                    item.status = 'error';
                    item.node.classList.add('is-error');
                    const errors = body.errors ? Object.values(body.errors).flat().join(' ') : (body.message || `Erreur ${xhr.status}`);
                    status.textContent = errors;
                }

                resolve();
            });
            xhr.addEventListener('error', () => {
                item.status = 'error';
                item.node.classList.add('is-error');
                status.textContent = 'Connexion interrompue.';
                resolve();
            });

            item.status = 'uploading';
            item.node.classList.remove('is-error');
            status.textContent = 'Envoi…';
            xhr.send(data);
        });
    }

    async function start() {
        const queue = items.filter((item) => item.status === 'pending' || item.status === 'error');

        if (!queue.length) {
            return;
        }

        startButton.disabled = true;
        const workers = Array.from({ length: 2 }, async () => {
            while (queue.length) {
                await upload(queue.shift());
                updateSummary();
            }
        });

        await Promise.all(workers);
        startButton.disabled = false;
        updateSummary();
    }

    function updateSummary(message = '') {
        const done = items.filter((i) => i.status === 'done').length;
        const failed = items.filter((i) => i.status === 'error').length;
        const pending = items.length - done - failed;
        summary.textContent = message || (items.length
            ? `${items.length} fichier(s) · ${done} importé(s)${failed ? ` · ${failed} en erreur` : ''}${pending ? ` · ${pending} à envoyer` : ''}`
            : 'Aucun fichier sélectionné.');
        startButton.disabled = !items.some((i) => i.status === 'pending' || i.status === 'error');
    }

    root.querySelector('[data-apply-defaults]')?.addEventListener('click', () => {
        items.filter((i) => i.status !== 'done').forEach((item) => {
            if (defaults.pack) {
                item.field('pack_id').value = defaults.pack.value;
            }

            if (defaults.category?.value) {
                item.field('category_id').value = defaults.category.value;
            }

            if (defaults.price?.value) {
                item.field('price').value = defaults.price.value;
            }

            if (defaults.tags?.value) {
                item.field('tags').value = defaults.tags.value;
            }
        });
    });

    input.addEventListener('change', () => {
        addFiles(input.files);
        input.value = '';
    });

    ['dragenter', 'dragover'].forEach((name) => dropzone.addEventListener(name, (event) => {
        event.preventDefault();
        dropzone.classList.add('is-over');
    }));

    ['dragleave', 'drop'].forEach((name) => dropzone.addEventListener(name, (event) => {
        event.preventDefault();
        dropzone.classList.remove('is-over');
    }));

    dropzone.addEventListener('drop', (event) => addFiles(event.dataTransfer.files));
    startButton.addEventListener('click', start);

    window.addEventListener('beforeunload', (event) => {
        if (items.some((i) => i.status === 'uploading' || i.status === 'pending')) {
            event.preventDefault();
        }
    });

    updateSummary();
}

/**
 * Remplacement d'aperçu sur la fiche d'édition : même génération côté navigateur.
 */
export function initPreviewReplacer() {
    const form = document.querySelector('[data-preview-replacer]');

    if (!form) {
        return;
    }

    const source = form.querySelector('[data-source]');
    const previewInput = form.querySelector('input[name="preview"]');
    const waveInput = form.querySelector('input[name="waveform"]');
    const status = form.querySelector('[data-replace-status]');

    source?.addEventListener('change', async () => {
        const file = source.files[0];

        if (!file) {
            return;
        }

        status.textContent = 'Analyse…';

        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const buffer = await ctx.decodeAudioData(await file.arrayBuffer());
            const seconds = Math.min(buffer.duration, 30);
            const offline = new OfflineAudioContext(1, Math.ceil(seconds * PREVIEW_RATE), PREVIEW_RATE);
            const node = offline.createBufferSource();
            node.buffer = buffer;
            node.connect(offline.destination);
            node.start(0);
            const rendered = await offline.startRendering();
            const samples = rendered.getChannelData(0);
            const blob = await new Promise((resolve) => resolve(wav(samples)));
            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], 'preview.wav', { type: 'audio/wav' }));
            previewInput.files = transfer.files;
            waveInput.value = JSON.stringify(computePeaks(buffer, 120));
            status.textContent = 'Nouvel aperçu prêt : enregistrez pour l’appliquer.';
        } catch {
            status.textContent = 'Ce fichier ne peut pas être lu par le navigateur. Essayez un WAV ou un MP3.';
        }
    });

    function wav(samples) {
        const bytes = new ArrayBuffer(44 + samples.length * 2);
        const view = new DataView(bytes);
        const text = (o, v) => [...v].forEach((c, i) => view.setUint8(o + i, c.charCodeAt(0)));
        text(0, 'RIFF');
        view.setUint32(4, 36 + samples.length * 2, true);
        text(8, 'WAVE');
        text(12, 'fmt ');
        view.setUint32(16, 16, true);
        view.setUint16(20, 1, true);
        view.setUint16(22, 1, true);
        view.setUint32(24, PREVIEW_RATE, true);
        view.setUint32(28, PREVIEW_RATE * 2, true);
        view.setUint16(32, 2, true);
        view.setUint16(34, 16, true);
        text(36, 'data');
        view.setUint32(40, samples.length * 2, true);

        for (let i = 0; i < samples.length; i++) {
            const s = Math.max(-1, Math.min(1, samples[i]));
            view.setInt16(44 + i * 2, s < 0 ? s * 0x8000 : s * 0x7fff, true);
        }

        return new Blob([bytes], { type: 'audio/wav' });
    }
}
