/**
 * Dessin des formes d'onde (barres verticales, partie lue en cyan lumineux).
 */
const PLAYED = '#5ce1ff';
const IDLE = 'rgba(92, 210, 255, 0.28)';

export function drawWave(canvas, peaks, progress = 0, options = {}) {
    if (!canvas) {
        return;
    }

    const ratio = window.devicePixelRatio || 1;
    const width = canvas.clientWidth || canvas.parentElement?.clientWidth || 300;
    const height = canvas.clientHeight || canvas.parentElement?.clientHeight || 40;

    if (canvas.width !== Math.round(width * ratio) || canvas.height !== Math.round(height * ratio)) {
        canvas.width = Math.round(width * ratio);
        canvas.height = Math.round(height * ratio);
    }

    const ctx = canvas.getContext('2d');
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    ctx.clearRect(0, 0, width, height);

    const values = peaks && peaks.length ? peaks : placeholder(64);
    const gap = options.gap ?? 2;
    const bar = Math.max(1, options.bar ?? 2);
    const count = Math.max(1, Math.floor(width / (bar + gap)));
    const mid = height / 2;
    const playedUntil = progress * count;

    for (let i = 0; i < count; i++) {
        const value = sample(values, i / count);
        const h = Math.max(1, value * (height - 2));
        const played = i < playedUntil;

        ctx.fillStyle = played ? PLAYED : IDLE;
        ctx.shadowColor = played ? 'rgba(92, 225, 255, 0.9)' : 'transparent';
        ctx.shadowBlur = played ? 6 : 0;
        ctx.fillRect(i * (bar + gap), mid - h / 2, bar, h);
    }

    if (progress > 0 && progress < 1) {
        const x = progress * width;
        ctx.shadowBlur = 10;
        ctx.shadowColor = '#ffffff';
        ctx.fillStyle = '#e6f8ff';
        ctx.fillRect(x, 0, 1, height);
    }
}

function sample(values, position) {
    const index = Math.min(values.length - 1, Math.floor(position * values.length));

    return Math.max(0.04, Math.min(1, Number(values[index]) || 0));
}

function placeholder(n) {
    return Array.from({ length: n }, (_, i) => 0.25 + 0.2 * Math.abs(Math.sin(i * 0.7)));
}

/**
 * Calcule `buckets` pics normalisés (0–1) à partir d'un AudioBuffer.
 */
export function computePeaks(audioBuffer, buckets = 120) {
    const channels = audioBuffer.numberOfChannels;
    const length = audioBuffer.length;
    const size = Math.max(1, Math.floor(length / buckets));
    const peaks = new Array(buckets).fill(0);
    const data = [];

    for (let c = 0; c < channels; c++) {
        data.push(audioBuffer.getChannelData(c));
    }

    for (let b = 0; b < buckets; b++) {
        let max = 0;
        const start = b * size;
        const end = Math.min(length, start + size);

        for (let i = start; i < end; i += 4) {
            for (let c = 0; c < channels; c++) {
                const v = Math.abs(data[c][i]);

                if (v > max) {
                    max = v;
                }
            }
        }

        peaks[b] = max;
    }

    const top = Math.max(...peaks, 0.0001);

    return peaks.map((v) => Math.round((v / top) * 1000) / 1000);
}

export function renderStaticWaves(root = document) {
    root.querySelectorAll('canvas[data-peaks]').forEach((canvas) => {
        try {
            drawWave(canvas, JSON.parse(canvas.dataset.peaks || '[]'), 0);
        } catch {
            drawWave(canvas, [], 0);
        }
    });
}
