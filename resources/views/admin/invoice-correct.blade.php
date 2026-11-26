@extends('admin.layout')

@section('title', 'Corriger '.$invoice->number)

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow"><a href="{{ route('admin.invoices.show', $invoice) }}">{{ $invoice->number }}</a></p>
            <h1>Corriger la facture</h1>
            <p class="lede">Une facture émise ne se modifie pas : un avoir l’annule intégralement, puis une nouvelle facture est émise avec les informations corrigées. Les montants ne changent pas.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.invoices.correct.store', $invoice) }}" class="panel" style="max-width: 760px">
        @csrf
        <div class="fields">
            <div class="field">
                <label for="buyer_name">Nom du client</label>
                <input id="buyer_name" name="buyer_name" value="{{ old('buyer_name', $invoice->buyer_name) }}" required maxlength="120">
            </div>
            <div class="field">
                <label for="buyer_email">E-mail</label>
                <input id="buyer_email" name="buyer_email" type="email" value="{{ old('buyer_email', $invoice->buyer_email) }}" maxlength="190">
            </div>
            <div class="field">
                <label for="buyer_company">Société</label>
                <input id="buyer_company" name="buyer_company" value="{{ old('buyer_company', $invoice->buyer_company) }}" maxlength="160">
            </div>
            <div class="field">
                <label for="buyer_vat">N° TVA</label>
                <input id="buyer_vat" name="buyer_vat" value="{{ old('buyer_vat', $invoice->buyer_vat) }}" maxlength="40">
            </div>
        </div>
        <div class="field" style="margin-top: 16px">
            <label for="buyer_address">Adresse</label>
            <textarea id="buyer_address" name="buyer_address" rows="3" maxlength="400">{{ old('buyer_address', $invoice->buyer_address) }}</textarea>
        </div>
        <label class="check" style="margin-top: 16px"><input type="checkbox" name="send" value="1" checked> <span>Envoyer l’avoir et la nouvelle facture au client par e-mail</span></label>
        <div class="form-actions">
            <button class="btn btn-primary" onclick="return confirm('Émettre l’avoir et la nouvelle facture ?')">Émettre l’avoir et la facture corrigée</button>
            <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-ghost">Annuler</a>
        </div>
    </form>
@endsection
