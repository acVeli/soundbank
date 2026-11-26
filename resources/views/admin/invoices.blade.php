@extends('admin.layout')

@section('title', 'Factures')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Administration · Comptabilité</p>
            <h1>Factures & avoirs</h1>
            <p class="lede">Émises automatiquement à chaque paiement confirmé. Numérotation continue par série et par année ; une correction passe par un avoir.</p>
        </div>
        <a href="{{ route('admin.invoices.export', ['du' => $from->format('Y-m-d'), 'au' => $to->format('Y-m-d')]) }}" class="btn"><x-icon name="download" size="14" /> Export CSV</a>
    </div>

    <form method="GET" class="row" style="margin-bottom: 18px">
        <div class="field"><label for="du">Du</label><input id="du" type="date" name="du" value="{{ $from->format('Y-m-d') }}"></div>
        <div class="field" style="margin-top: 0"><label for="au">Au</label><input id="au" type="date" name="au" value="{{ $to->format('Y-m-d') }}"></div>
        <div class="field" style="margin-top: 0; flex: 1; max-width: 320px"><label for="q">Recherche</label><input id="q" type="search" name="q" value="{{ $search }}" placeholder="N°, client, e-mail"></div>
        <button class="btn" style="align-self: flex-end">Filtrer</button>
    </form>

    <div class="grid-3" style="margin-bottom: 20px">
        <x-stat label="Total HT" :value="\App\Support\Money::format($totals['ht'])" />
        <x-stat label="TVA" :value="\App\Support\Money::format($totals['vat'])" />
        <x-stat label="Total TTC" :value="\App\Support\Money::format($totals['ttc'])" accent />
    </div>

    <div class="panel table-wrap">
        @if ($invoices->isEmpty())
            <p class="muted">Aucun document sur cette période.</p>
        @else
            <table class="table">
                <thead><tr><th>Numéro</th><th>Type</th><th>Date</th><th>Client</th><th class="num">HT</th><th class="num">TTC</th><th>Envoi</th><th></th></tr></thead>
                <tbody>
                    @foreach ($invoices as $invoice)
                        <tr>
                            <td><a href="{{ route('admin.invoices.show', $invoice) }}" class="mono">{{ $invoice->number }}</a></td>
                            <td>
                                <span @class(['badge', 'badge-warn' => $invoice->isCreditNote()])>{{ $invoice->label() }}</span>
                                @if ($invoice->creditNote)<span class="badge badge-muted" title="Annulée par {{ $invoice->creditNote->number }}">Annulée</span>@endif
                            </td>
                            <td>{{ $invoice->issued_at->format('d/m/Y') }}</td>
                            <td>{{ $invoice->buyer_company ?: $invoice->buyer_name }}<div class="small dim">{{ $invoice->buyer_email }}</div></td>
                            <td class="num">{{ $invoice->money($invoice->total_ht_cents) }}</td>
                            <td class="num">{{ $invoice->money($invoice->total_ttc_cents) }}</td>
                            <td class="small muted">{{ $invoice->sent_at ? $invoice->sent_at->format('d/m H:i') : '—' }}</td>
                            <td class="right"><a href="{{ route('admin.invoices.show', $invoice) }}">Ouvrir →</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    {{ $invoices->links() }}
@endsection
