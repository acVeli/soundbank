<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>{{ $invoice->label() }} {{ $invoice->number }}</title></head>
<body style="margin:0;padding:24px;background:#020408;font-family:Segoe UI,Arial,sans-serif;color:#cfe9f7;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#07101a;border:1px solid #1d4a63;">
        <tr><td style="height:2px;background:#5ce1ff;"></td></tr>
        <tr><td style="padding:28px;">
            <p style="margin:0 0 8px;font-size:12px;letter-spacing:.3em;text-transform:uppercase;color:#5ce1ff;">{{ $invoice->seller['name'] ?? config('app.name') }}</p>
            <h1 style="margin:0 0 16px;font-size:24px;color:#e6f8ff;letter-spacing:.04em;">{{ $invoice->label() }} {{ $invoice->number }}</h1>
            <p style="margin:0 0 12px;">Bonjour {{ $invoice->buyer_name }},</p>
            @if ($invoice->isCreditNote())
                <p style="margin:0 0 12px;">Voici l’avoir {{ $invoice->number }} qui annule la facture {{ $invoice->creditedInvoice?->number }}. Une facture corrigée vous est envoyée séparément.</p>
            @else
                <p style="margin:0 0 12px;">Merci pour votre achat. Vos sons sont disponibles dans votre bibliothèque et votre facture est consultable en ligne (impression ou PDF).</p>
            @endif
            <table role="presentation" width="100%" style="margin:16px 0;border-collapse:collapse;font-size:15px;">
                <tr><td style="padding:6px 0;color:#7a95a8;">Date</td><td style="padding:6px 0;text-align:right;">{{ $invoice->issued_at->locale('fr')->isoFormat('D MMMM YYYY') }}</td></tr>
                <tr><td style="padding:6px 0;color:#7a95a8;">Montant TTC</td><td style="padding:6px 0;text-align:right;font-weight:600;color:#e6f8ff;">{{ $invoice->money($invoice->total_ttc_cents) }}</td></tr>
            </table>
            <p style="margin:20px 0;">
                <a href="{{ $invoice->publicUrl() }}" style="display:inline-block;padding:12px 22px;background:#5ce1ff;color:#020408;text-decoration:none;font-weight:700;letter-spacing:.08em;text-transform:uppercase;font-size:13px;">Voir {{ $invoice->isCreditNote() ? 'l’avoir' : 'la facture' }}</a>
                @unless ($invoice->isCreditNote())
                    &nbsp;<a href="{{ route('account.library') }}" style="color:#5ce1ff;">Ma bibliothèque →</a>
                @endunless
            </p>
            <p style="margin:0;font-size:13px;color:#7a95a8;">Ce lien est personnel : ne le partagez pas.</p>
        </td></tr>
    </table>
</body>
</html>
