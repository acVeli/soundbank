@extends('studio.layout')

@section('title', 'Versements')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Studio · Revenus</p>
            <h1>Versements</h1>
        </div>
    </div>

    <div class="grid-4">
        <x-stat label="Disponible" :value="\App\Support\Money::format($available)" accent />
        <x-stat label="En cours de versement" :value="\App\Support\Money::format($pending)" />
        <x-stat label="Déjà versé" :value="\App\Support\Money::format($paidOut)" />
        <x-stat label="Gains totaux" :value="\App\Support\Money::format($earned)" />
    </div>

    <div class="grid-2" style="margin-top: 24px">
        <form method="POST" action="{{ route('studio.payouts.store') }}" class="panel panel-glow">
            @csrf
            <p class="panel-title">Demander un versement</p>
            @if (blank($profile->payoutDestination()))
                <p class="muted">Renseignez d’abord votre compte PayPal ou votre IBAN.</p>
                <div class="form-actions"><a href="{{ route('studio.profile') }}" class="btn">Coordonnées de versement</a></div>
            @else
                <p class="muted small" style="margin-bottom: 14px">Vers : <span class="mono">{{ $profile->payout_method === 'bank' ? 'Virement — '.$profile->payout_holder : 'PayPal — '.$profile->payout_email }}</span> · <a href="{{ route('studio.profile') }}">modifier</a></p>
                <div class="field">
                    <label for="amount">Montant (€) <span>minimum {{ \App\Support\Money::format($minimum) }}</span></label>
                    <input id="amount" name="amount" type="number" step="0.01" min="{{ $minimum / 100 }}" max="{{ $available / 100 }}" value="{{ old('amount', \App\Support\Money::input($available)) }}" required>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" @disabled($available < $minimum || $available === 0)>Demander le versement</button>
                </div>
                @if ($available < $minimum)
                    <p class="hint">Votre solde doit atteindre {{ \App\Support\Money::format($minimum) }} pour demander un versement.</p>
                @endif
            @endif
        </form>

        <div class="panel">
            <p class="panel-title">Comment ça marche</p>
            <ul class="muted" style="padding-left: 18px; display: grid; gap: 8px">
                <li>Chaque vente payée crédite immédiatement votre part (prix moins la commission de la plateforme).</li>
                <li>Vous demandez un versement quand vous voulez, dès le minimum atteint.</li>
                <li>L’équipe effectue le paiement PayPal ou le virement, puis le marque comme versé avec sa référence.</li>
            </ul>
        </div>
    </div>

    <div class="panel table-wrap" style="margin-top: 24px">
        <p class="panel-title">Historique</p>
        @if ($payouts->isEmpty())
            <p class="muted">Aucune demande pour l’instant.</p>
        @else
            <table class="table">
                <thead><tr><th>Date</th><th>Moyen</th><th class="num">Montant</th><th>Statut</th><th>Référence / note</th></tr></thead>
                <tbody>
                    @foreach ($payouts as $payout)
                        <tr>
                            <td>{{ $payout->created_at->format('d/m/Y') }}</td>
                            <td>{{ $payout->methodLabel() }}</td>
                            <td class="num">{{ $payout->amount() }}</td>
                            <td>
                                <span @class(['badge', 'badge-ok' => $payout->status === 'paid', 'badge-warn' => $payout->status === 'rejected'])>{{ $payout->statusLabel() }}</span>
                            </td>
                            <td class="small muted">{{ $payout->reference }} {{ $payout->admin_note }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $payouts->links() }}
        @endif
    </div>
@endsection
