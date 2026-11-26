@props(['label', 'value', 'hint' => null, 'accent' => false])
<div @class(['panel stat', 'stat-accent' => $accent])>
    <p class="label">{{ $label }}</p>
    <strong>{{ $value }}</strong>
    @if ($hint)<small>{{ $hint }}</small>@endif
</div>
