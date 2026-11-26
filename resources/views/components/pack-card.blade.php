@props(['pack'])
<a href="{{ route('packs.show', $pack) }}" class="card">
    <x-cover :pack="$pack" />
    <div class="card-body">
        <strong>{{ $pack->title }}</strong>
        <div class="meta">
            <span>{{ $pack->artist?->displayName() }}</span>
            <span class="price">{{ $pack->price() }}</span>
        </div>
        <div class="meta">
            <span class="dim">{{ $pack->samples_count ?? $pack->samples()->count() }} sons{{ $pack->genre ? ' · '.$pack->genre : '' }}</span>
        </div>
    </div>
</a>
