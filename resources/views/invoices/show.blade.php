@php
    $seller = $invoice->seller ?? [];
    $isCredit = $invoice->isCreditNote();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    <title>{{ $invoice->label() }} {{ $invoice->number }}</title>
    <link rel="stylesheet" href="{{ asset('css/invoice.css') }}?v={{ filemtime(public_path('css/invoice.css')) }}">
</head>
<body>
    <nav class="toolbar no-print">
        @if (! empty($backUrl))
            <a href="{{ $backUrl }}">← Retour</a>
        @endif
        <button type="button" onclick="window.print()">Imprimer / enregistrer en PDF</button>
        @if (! empty($admin))
            @if (filled($invoice->buyer_email))
                <form method="POST" action="{{ route('admin.invoices.send', $invoice) }}">
                    @csrf
                    <button type="submit">{{ $invoice->sent_at ? 'Renvoyer' : 'Envoyer' }} au client</button>
                </form>
            @endif
            @if (! $isCredit && ! $invoice->isCancelled())
                <a href="{{ route('admin.invoices.correct', $invoice) }}">Corriger (avoir + nouvelle facture)</a>
            @endif
            @if ($invoice->order)
                <a href="{{ route('admin.orders.show', $invoice->order) }}">Commande {{ $invoice->order->number }}</a>
            @endif
        @endif
    </nav>

    @if (session('status'))
        <p class="flash no-print">{{ session('status') }}</p>
    @endif
    @if (! empty($admin))
        <p class="flash no-print">
            {{ $invoice->sent_at ? 'Envoyée au client le '.$invoice->sent_at->format('d/m/Y à H:i').'.' : 'Pas encore envoyée par e-mail.' }}
            Lien client : <a href="{{ $invoice->publicUrl() }}">{{ $invoice->publicUrl() }}</a>
        </p>
    @endif

    <article class="sheet">
        <header class="head">
            <div>
                <p class="brand"><span class="mark"></span>{{ $seller['name'] ?? config('app.name') }}</p>
                @if (filled($seller['legal'] ?? null))<p>{{ $seller['legal'] }}</p>@endif
                @if (filled($seller['address'] ?? null))<p class="pre">{{ $seller['address'] }}</p>@endif
                @if (filled($seller['siret'] ?? null))<p>SIRET : {{ $seller['siret'] }}</p>@endif
                @if (filled($seller['vat_number'] ?? null))<p>TVA intracommunautaire : {{ $seller['vat_number'] }}</p>@endif
                @if (filled($seller['email'] ?? null))<p>{{ $seller['email'] }}</p>@endif
            </div>
            <div class="doc">
                <p class="doc-type">{{ $invoice->label() }}</p>
                <p class="doc-number">{{ $invoice->number }}</p>
                <p>Date d’émission : {{ $invoice->issued_at->format('d/m/Y') }}</p>
                @if ($invoice->order)
                    <p>Commande : {{ $invoice->order->number }}</p>
                @endif
                @if ($isCredit && $invoice->creditedInvoice)
                    <p>Annule la facture {{ $invoice->creditedInvoice->number }}</p>
                @endif
                @if (! $isCredit && $invoice->isCancelled())
                    <p class="cancelled">Annulée par l’avoir {{ $invoice->creditNote->number }}</p>
                @endif
            </div>
        </header>

        <section class="buyer">
            <p class="caption">{{ $isCredit ? 'Client' : 'Facturé à' }}</p>
            <p class="strong">{{ $invoice->buyer_company ?: $invoice->buyer_name }}</p>
            @if (filled($invoice->buyer_company))<p>{{ $invoice->buyer_name }}</p>@endif
            @if (filled($invoice->buyer_address))<p class="pre">{{ $invoice->buyer_address }}</p>@endif
            @if (filled($invoice->buyer_vat))<p>TVA : {{ $invoice->buyer_vat }}</p>@endif
            @if (filled($invoice->buyer_email))<p>{{ $invoice->buyer_email }}</p>@endif
        </section>

        <table class="lines">
            <thead>
                <tr>
                    <th>Désignation</th>
                    <th class="num">Qté</th>
                    <th class="num">PU HT</th>
                    <th class="num">TVA</th>
                    <th class="num">Total HT</th>
                </tr>
            </thead>
            <tbody>
                @foreach ((array) $invoice->lines as $line)
                    <tr>
                        <td>{{ $line['description'] ?? '' }}</td>
                        <td class="num">{{ $line['quantity'] ?? 1 }}</td>
                        <td class="num">{{ $invoice->money((int) ($line['unit_ht_cents'] ?? 0)) }}</td>
                        <td class="num">{{ $invoice->vatRateLabel() }}</td>
                        <td class="num">{{ $invoice->money((int) ($line['total_ht_cents'] ?? 0)) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr><td>Total HT</td><td class="num">{{ $invoice->money($invoice->total_ht_cents) }}</td></tr>
            <tr><td>TVA ({{ $invoice->vatRateLabel() }})</td><td class="num">{{ $invoice->money($invoice->total_vat_cents) }}</td></tr>
            <tr class="grand"><td>Total TTC</td><td class="num">{{ $invoice->money($invoice->total_ttc_cents) }}</td></tr>
        </table>

        @if (filled($invoice->vat_mention))
            <p class="mention">{{ $invoice->vat_mention }}</p>
        @endif

        <section class="payment">
            @if ($isCredit)
                <p>Le montant de cet avoir vient en déduction de la facture annulée. Aucun remboursement n’est effectué automatiquement.</p>
            @else
                <p>Facture acquittée le {{ $invoice->paid_at?->format('d/m/Y') }} par {{ $invoice->payment_method }}.</p>
                @if (filled($invoice->payment_reference))
                    <p class="muted">Référence de transaction : {{ $invoice->payment_reference }}</p>
                @endif
                <p class="muted">Biens numériques livrés immédiatement après paiement : le client a expressément renoncé à son droit de rétractation (art. L221-28 du Code de la consommation).</p>
            @endif
        </section>

        @if (filled($seller['footer'] ?? null))
            <footer class="pre">{{ $seller['footer'] }}</footer>
        @endif
    </article>
</body>
</html>
