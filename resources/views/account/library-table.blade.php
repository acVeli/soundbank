<div class="tracklist">
    @foreach ($items as $item)
        @php($sample = $item->sample)
        @continue($sample === null)
        <div class="track track-library" data-track='@json($sample->playerPayload())' data-track-id="{{ $sample->id }}">
            <button type="button" class="play" data-play aria-label="Écouter" @disabled(! $sample->preview_path)>
                <x-icon name="play" class="i-play" size="16" />
                <x-icon name="pause" class="i-pause" size="16" />
            </button>
            <div class="track-title">
                @if ($sample->trashed() || ! $sample->isPublished())
                    <span style="font-weight: 600; color: var(--ice)">{{ $sample->title }}</span>
                @else
                    <a href="{{ route('samples.show', $sample) }}">{{ $sample->title }}</a>
                @endif
                <span class="by">{{ $sample->artist?->displayName() }}</span>
            </div>
            <div class="wave"><canvas data-peaks='@json($sample->peaks())'></canvas></div>
            <span class="track-cat">{{ $sample->category?->name ?? '—' }}</span>
            <span class="track-meta">{{ strtoupper($sample->file_format) }}<br>{{ $sample->fileSize() }}</span>
            <div class="track-buy">
                <a href="{{ route('account.library.license', $item) }}" class="btn btn-sm btn-ghost" title="Certificat de licence"><x-icon name="key" size="14" /></a>
                <a href="{{ route('account.library.download', $item) }}" class="btn btn-sm btn-primary"><x-icon name="download" size="14" /> Télécharger</a>
            </div>
        </div>
    @endforeach
</div>
