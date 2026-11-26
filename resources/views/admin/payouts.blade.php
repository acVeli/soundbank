@extends('admin.layout')

@section('title', 'Versements')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Administration</p>
            <h1>Versements aux artistes</h1>
            <p class="lede">Effectuez le paiement depuis votre compte PayPal ou votre banque, puis marquez la demande comme versée avec la référence de transaction.</p>
        </div>
    </div>

    <div class="grid-2" style="margin-bottom: 20px">
        <x-stat label="À verser (demandes en attente)" :value="\App\Support\Money::format($totals['requested'])" accent />
        <x-stat label="Déjà versé" :value="\App\Support\Money::format($totals['paid'])" />
    </div>

    <div class="subnav">
        @foreach (['requested' => 'En attente', 'paid' => 'Versés', 'rejected' => 'Refusés', 'all' => 'Tous'] as $value => $label)
            <a href="{{ route('admin.payouts', ['statut' => $value]) }}" @class(['is-active' => $status === $value])>{{ $label }}</a>
        @endforeach
    </div>

    @if ($payouts->isEmpty())
        <x-empty title="Rien à traiter" />
    @else
        <div class="stack">
            @foreach ($payouts as $payout)
                <div class="panel">
                    <div class="row between">
                        <div>
                            <strong style="color: var(--ice)">{{ $payout->artist?->displayName() }}</strong>
                            <span class="muted small">· {{ $payout->artist?->email }} · demandé le {{ $payout->created_at->format('d/m/Y H:i') }}</span>
                            <p class="mono small" style="margin-top: 6px">{{ $payout->methodLabel() }} → <span style="color: var(--cyan)">{{ $payout->destination }}</span></p>
                        </div>
                        <div class="row">
                            <span class="price-lg" style="font-size: 1.4rem">{{ $payout->amount() }}</span>
                            <span @class(['badge', 'badge-ok' => $payout->status === 'paid', 'badge-warn' => $payout->status === 'rejected'])>{{ $payout->statusLabel() }}</span>
                        </div>
                    </div>

                    @if ($payout->status === 'requested')
                        <form method="POST" action="{{ route('admin.payouts.update', $payout) }}" class="row" style="margin-top: 14px; align-items: flex-end">
                            @csrf
                            @method('PATCH')
                            <div class="field" style="flex: 1; min-width: 200px">
                                <label for="reference-{{ $payout->id }}">Référence de transaction</label>
                                <input id="reference-{{ $payout->id }}" name="reference" maxlength="190" placeholder="ID PayPal / référence de virement">
                            </div>
                            <div class="field" style="flex: 1; min-width: 200px; margin-top: 0">
                                <label for="note-{{ $payout->id }}">Note <span>visible par l’artiste</span></label>
                                <input id="note-{{ $payout->id }}" name="admin_note" maxlength="500">
                            </div>
                            <button name="decision" value="paid" class="btn btn-primary">Marquer versé</button>
                            <button name="decision" value="rejected" class="btn btn-danger" onclick="return confirm('Refuser cette demande ? Le montant revient dans le solde de l’artiste.')">Refuser</button>
                        </form>
                    @else
                        <p class="small muted" style="margin-top: 10px">Traité le {{ $payout->processed_at?->format('d/m/Y H:i') }} {{ $payout->reference ? '· réf. '.$payout->reference : '' }} {{ $payout->admin_note ? '· '.$payout->admin_note : '' }}</p>
                    @endif
                </div>
            @endforeach
        </div>
        {{ $payouts->links() }}
    @endif
@endsection
