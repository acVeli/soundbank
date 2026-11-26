@extends('admin.layout')

@section('title', 'Artistes')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Administration</p>
            <h1>Artistes</h1>
        </div>
        <form method="GET" class="row">
            <input type="search" name="q" value="{{ $search }}" placeholder="Nom, e-mail" style="width: 260px" aria-label="Rechercher">
            <button class="btn">Chercher</button>
        </form>
    </div>

    <div class="panel table-wrap">
        @if ($artists->isEmpty())
            <p class="muted">Aucun artiste.</p>
        @else
            <table class="table">
                <thead><tr><th>Artiste</th><th>Compte</th><th>Inscrit</th><th class="num">Sons</th><th class="num">Gains</th><th class="num">Solde</th><th>Versement</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                    @foreach ($artists as $artist)
                        @php($profile = $artist->artistProfile)
                        <tr>
                            <td>
                                <div class="row" style="gap: 10px; flex-wrap: nowrap">
                                    <x-avatar :profile="$profile" size="32" />
                                    <span>
                                        @if ($profile)
                                            <a href="{{ route('artists.show', $profile) }}">{{ $profile->stage_name }}</a>
                                            @if ($profile->is_verified)<span class="verified" title="Vérifié">✓</span>@endif
                                        @else
                                            <span class="dim">Sans profil</span>
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td>{{ $artist->name }}<div class="small dim">{{ $artist->email }}</div></td>
                            <td>{{ $artist->created_at->format('d/m/Y') }}</td>
                            <td class="num">{{ $artist->samples_count }}</td>
                            <td class="num">{{ \App\Support\Money::format((int) $artist->earned_cents) }}</td>
                            <td class="num">{{ \App\Support\Money::format((int) $artist->available_cents) }}</td>
                            <td class="small muted">{{ $profile?->payout_method === 'bank' ? 'Virement' : 'PayPal' }}{{ $profile?->payoutDestination() ? '' : ' (non renseigné)' }}</td>
                            <td>
                                @if ($artist->isActive())
                                    <span class="badge badge-ok">Actif</span>
                                @else
                                    <span class="badge badge-warn">Suspendu</span>
                                @endif
                            </td>
                            <td class="right">
                                <div class="row" style="justify-content: flex-end; gap: 6px; flex-wrap: nowrap">
                                    @if ($profile)
                                        <form method="POST" action="{{ route('admin.artists.verify', $artist) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-ghost">{{ $profile->is_verified ? 'Retirer badge' : 'Vérifier' }}</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.artists.status', $artist) }}" data-confirm="{{ $artist->isActive() ? 'Suspendre cet artiste ? Son catalogue sera masqué.' : 'Réactiver cet artiste ?' }}">
                                        @csrf
                                        @method('PATCH')
                                        <button @class(['btn btn-sm', 'btn-danger' => $artist->isActive()])>{{ $artist->isActive() ? 'Suspendre' : 'Réactiver' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    {{ $artists->links() }}
@endsection
