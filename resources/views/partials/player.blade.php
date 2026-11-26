<div class="player" data-player aria-label="Lecteur d’aperçus">
    <button type="button" class="play" data-player-toggle aria-label="Lecture / pause">
        <x-icon name="play" class="i-play" />
        <x-icon name="pause" class="i-pause" />
    </button>
    <div class="player-info">
        <a href="#" data-player-title>—</a>
        <span data-player-artist></span>
    </div>
    <div class="wave" data-player-wave><canvas></canvas></div>
    <span class="player-time" data-player-time>0:00 / 0:00</span>
    <div class="row">
        <input type="range" class="player-volume" min="0" max="1" step="0.01" data-player-volume aria-label="Volume">
        <button type="button" class="icon-btn" data-player-close aria-label="Fermer le lecteur"><x-icon name="x" /></button>
    </div>
</div>
