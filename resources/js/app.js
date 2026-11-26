import { initPlayer } from './player';
import { initUi } from './ui';
import { initPreviewReplacer, initUploader } from './uploader';
import { renderStaticWaves } from './waveform';

document.addEventListener('DOMContentLoaded', () => {
    initUi();
    initPlayer();
    initUploader();
    initPreviewReplacer();
    renderStaticWaves();

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => renderStaticWaves(), 150);
    });
});
