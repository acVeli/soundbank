@props(['profile'])
<a href="{{ route('artists.show', $profile) }}" class="panel artist-card">
    <x-avatar :profile="$profile" />
    <div style="min-width: 0">
        <strong class="row" style="gap: 8px; color: var(--ice)">
            {{ $profile->stage_name }}
            @if ($profile->is_verified)<span class="verified" title="Artiste vérifié">✓</span>@endif
        </strong>
        <span class="small muted">
            {{ $profile->location ?: 'Artiste' }}
            @isset($profile->samples_count) · {{ $profile->samples_count }} sons @endisset
        </span>
    </div>
</a>
