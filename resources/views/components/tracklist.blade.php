@props(['samples', 'owned' => [], 'favorites' => [], 'showArtist' => true, 'empty' => 'Aucun son pour le moment.'])
@if ($samples->isEmpty())
    <x-empty :title="$empty">{{ $slot }}</x-empty>
@else
    <div class="tracklist">
        @foreach ($samples as $sample)
            <x-sample-row :sample="$sample" :owned="$owned" :favorites="$favorites" :show-artist="$showArtist" />
        @endforeach
    </div>
@endif
