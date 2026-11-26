import { drawWave } from './waveform';

/**
 * Lecteur global : un seul <audio>, piloté par les boutons des listes.
 * Chaque élément [data-track] porte le JSON du son (titre, aperçu, pics…).
 */
export function initPlayer() {
    const bar = document.querySelector('[data-player]');

    if (!bar) {
        return;
    }

    const audio = new Audio();
    audio.preload = 'none';
    const els = {
        toggle: bar.querySelector('[data-player-toggle]'),
        title: bar.querySelector('[data-player-title]'),
        artist: bar.querySelector('[data-player-artist]'),
        wave: bar.querySelector('[data-player-wave] canvas'),
        waveBox: bar.querySelector('[data-player-wave]'),
        time: bar.querySelector('[data-player-time]'),
        volume: bar.querySelector('[data-player-volume]'),
        close: bar.querySelector('[data-player-close]'),
    };

    let current = null;
    const counted = new Set();
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    try {
        const stored = parseFloat(localStorage.getItem('sb-volume'));

        if (!Number.isNaN(stored)) {
            audio.volume = stored;
        }
    } catch {
        // Stockage indisponible : volume par défaut.
    }

    if (els.volume) {
        els.volume.value = audio.volume;
        els.volume.addEventListener('input', () => {
            audio.volume = parseFloat(els.volume.value);

            try {
                localStorage.setItem('sb-volume', String(audio.volume));
            } catch {
                // Ignoré.
            }
        });
    }

    function trackEls(id) {
        return document.querySelectorAll(`[data-track-id="${id}"]`);
    }

    function setPlayingState() {
        document.querySelectorAll('.is-playing').forEach((el) => el.classList.remove('is-playing'));

        if (current && !audio.paused) {
            trackEls(current.id).forEach((el) => el.classList.add('is-playing'));
            bar.classList.add('is-playing');
            els.toggle?.classList.add('is-playing');
        } else {
            els.toggle?.classList.remove('is-playing');
        }
    }

    function format(seconds) {
        if (!Number.isFinite(seconds)) {
            return '0:00';
        }

        const s = Math.floor(seconds);

        return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
    }

    function redraw() {
        const progress = audio.duration ? audio.currentTime / audio.duration : 0;

        if (current) {
            drawWave(els.wave, current.peaks, progress, { bar: 2, gap: 2 });
            trackEls(current.id).forEach((el) => {
                const canvas = el.querySelector('.wave canvas');

                if (canvas) {
                    drawWave(canvas, current.peaks, progress);
                }
            });
        }

        if (els.time) {
            els.time.textContent = `${format(audio.currentTime)} / ${format(audio.duration)}`;
        }
    }

    function load(track, startAt = 0) {
        if (current && current.id !== track.id) {
            trackEls(current.id).forEach((el) => {
                const canvas = el.querySelector('.wave canvas');

                if (canvas) {
                    drawWave(canvas, current.peaks, 0);
                }
            });
        }

        current = track;
        audio.src = track.src;
        els.title.textContent = track.title;
        els.title.href = track.url;
        els.artist.textContent = track.artist || '';
        bar.classList.add('is-active');

        audio.addEventListener('loadedmetadata', () => {
            if (startAt > 0 && audio.duration) {
                audio.currentTime = startAt * audio.duration;
            }
        }, { once: true });

        play();
    }

    function play() {
        audio.play().catch(() => {});

        if (current && !counted.has(current.id) && current.play && csrf) {
            counted.add(current.id);
            fetch(current.play, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } }).catch(() => {});
        }
    }

    function toggle(track, startAt = null) {
        if (!track.src) {
            return;
        }

        if (current && current.id === track.id) {
            if (startAt !== null && audio.duration) {
                audio.currentTime = startAt * audio.duration;

                if (audio.paused) {
                    play();
                }

                return;
            }

            audio.paused ? play() : audio.pause();

            return;
        }

        load(track, startAt ?? 0);
    }

    function trackFrom(el) {
        const holder = el.closest('[data-track]');

        if (!holder) {
            return null;
        }

        try {
            return JSON.parse(holder.dataset.track);
        } catch {
            return null;
        }
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-play]');

        if (button) {
            event.preventDefault();
            const track = trackFrom(button);

            if (track) {
                toggle(track);
            }

            return;
        }

        const wave = event.target.closest('[data-track] .wave');

        if (wave) {
            const track = trackFrom(wave);
            const rect = wave.getBoundingClientRect();

            if (track) {
                toggle(track, Math.min(1, Math.max(0, (event.clientX - rect.left) / rect.width)));
            }
        }
    });

    els.toggle?.addEventListener('click', () => {
        if (current) {
            audio.paused ? play() : audio.pause();
        }
    });

    els.waveBox?.addEventListener('click', (event) => {
        if (!audio.duration) {
            return;
        }

        const rect = els.waveBox.getBoundingClientRect();
        audio.currentTime = ((event.clientX - rect.left) / rect.width) * audio.duration;
    });

    els.close?.addEventListener('click', () => {
        audio.pause();
        bar.classList.remove('is-active');
    });

    document.addEventListener('keydown', (event) => {
        const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);

        if (event.code === 'Space' && current && !typing) {
            event.preventDefault();
            audio.paused ? play() : audio.pause();
        }
    });

    ['play', 'pause', 'ended'].forEach((name) => audio.addEventListener(name, () => {
        setPlayingState();
        redraw();
    }));

    audio.addEventListener('timeupdate', redraw);
    window.addEventListener('resize', redraw);
}
