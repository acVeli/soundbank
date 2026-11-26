/**
 * Fabrication de l'aperçu public d'un son, entièrement dans le navigateur :
 * 22 kHz mono, durée limitée, fondu de fin et tag sonore « bip-bip » mixé
 * par-dessus (au début, puis à intervalle régulier). L'original n'est jamais
 * publié : seul ce fichier dégradé et marqué est écoutable sans achat.
 */
export const PREVIEW_RATE = 22050;

/**
 * Tag par défaut : deux notes brèves et aiguës, reconnaissables.
 */
export function synthTag(rate = PREVIEW_RATE) {
    const notes = [[1318.51, 0.09], [1975.53, 0.11]];
    const gap = 0.035;
    const total = notes.reduce((sum, [, length]) => sum + length, 0) + gap;
    const out = new Float32Array(Math.ceil(total * rate));
    let offset = 0;

    for (const [freq, length] of notes) {
        const n = Math.floor(length * rate);

        for (let i = 0; i < n; i++) {
            const t = i / rate;
            const env = Math.min(1, t / 0.004) * Math.exp(-t * 18);
            out[offset + i] = (Math.sin(2 * Math.PI * freq * t) + 0.3 * Math.sin(4 * Math.PI * freq * t)) * env * 0.8;
        }

        offset += n + Math.floor(gap * rate);
    }

    return out;
}

/**
 * Charge un tag personnalisé (fichier audio, ex. une voix « Soundbank »).
 * Retourne null en cas d'échec : le tag synthétique prend le relais.
 */
export async function loadTag(url) {
    if (!url) {
        return null;
    }

    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const buffer = await ctx.decodeAudioData(await (await fetch(url)).arrayBuffer());

        return await renderMono(buffer, PREVIEW_RATE, Math.min(buffer.duration, 3));
    } catch {
        return null;
    }
}

async function renderMono(buffer, rate, seconds, fadeOut = false) {
    const frames = Math.max(1, Math.ceil(seconds * rate));
    const offline = new OfflineAudioContext(1, frames, rate);
    const source = offline.createBufferSource();
    const gain = offline.createGain();
    source.buffer = buffer;
    source.connect(gain).connect(offline.destination);

    if (fadeOut && buffer.duration > seconds) {
        gain.gain.setValueAtTime(1, Math.max(0, seconds - 1.5));
        gain.gain.linearRampToValueAtTime(0, seconds);
    }

    source.start(0);
    const rendered = await offline.startRendering();

    return rendered.getChannelData(0);
}

/**
 * @param {AudioBuffer} buffer  son original décodé
 * @param {{seconds?: number, tag?: Float32Array|null, interval?: number}} options
 * @returns {Promise<Blob>} WAV 16 bits mono
 */
export async function buildPreview(buffer, { seconds = 30, tag = null, interval = 4 } = {}) {
    const tagSamples = tag ?? synthTag(PREVIEW_RATE);
    const tagSeconds = tagSamples.length / PREVIEW_RATE;
    const length = Math.max(Math.min(buffer.duration, seconds), tagSeconds + 0.15);
    const rendered = await renderMono(buffer, PREVIEW_RATE, length, true);
    const samples = new Float32Array(rendered);

    let peak = 0;

    for (const v of samples) {
        peak = Math.max(peak, Math.abs(v));
    }

    const level = 0.4 * Math.max(peak, 0.3);
    const step = Math.max(tagSeconds + 0.5, interval);

    for (let start = 0.02; start < length; start += step) {
        const offset = Math.floor(start * PREVIEW_RATE);

        for (let i = 0; i < tagSamples.length && offset + i < samples.length; i++) {
            samples[offset + i] += tagSamples[i] * level;
        }
    }

    let max = 0;

    for (const v of samples) {
        max = Math.max(max, Math.abs(v));
    }

    if (max > 0.98) {
        const scale = 0.98 / max;

        for (let i = 0; i < samples.length; i++) {
            samples[i] *= scale;
        }
    }

    return encodeWav(samples, PREVIEW_RATE);
}

export function encodeWav(samples, rate) {
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
