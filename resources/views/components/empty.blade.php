@props(['title'])
<div {{ $attributes->merge(['class' => 'empty']) }}>
    <strong>{{ $title }}</strong>
    {{ $slot }}
</div>
