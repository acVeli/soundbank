@props(['profile', 'size' => null])
<span {{ $attributes->merge(['class' => 'avatar']) }} @if ($size) style="--size: {{ $size }}px" @endif>
    @if ($profile?->avatarUrl())
        <img src="{{ $profile->avatarUrl() }}" alt="" loading="lazy">
    @else
        {{ $profile?->initials() ?? '?' }}
    @endif
</span>
