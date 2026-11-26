@props(['pack'])
<div {{ $attributes->merge(['class' => 'cover scan']) }}>
    @if ($pack->coverUrl())
        <img src="{{ $pack->coverUrl() }}" alt="Pochette du pack {{ $pack->title }}" loading="lazy">
    @else
        <span class="cover-title">{{ $pack->title }}</span>
    @endif
</div>
