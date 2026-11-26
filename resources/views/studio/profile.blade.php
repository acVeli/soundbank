@extends('studio.layout')

@section('title', 'Profil artiste')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Studio</p>
            <h1>Profil artiste</h1>
        </div>
        <a href="{{ route('artists.show', $profile) }}" class="btn btn-ghost">Voir ma page publique</a>
    </div>

    <form method="POST" action="{{ route('studio.profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="detail">
            <div class="panel">
                <p class="panel-title">Page publique</p>
                <div class="fields">
                    <div class="field">
                        <label for="stage_name">Nom d’artiste</label>
                        <input id="stage_name" name="stage_name" value="{{ old('stage_name', $profile->stage_name) }}" required maxlength="80">
                    </div>
                    <div class="field">
                        <label for="location">Ville / pays</label>
                        <input id="location" name="location" value="{{ old('location', $profile->location) }}" maxlength="80">
                    </div>
                </div>
                <div class="field" style="margin-top: 16px">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" rows="5" maxlength="2000" placeholder="Votre univers, votre voix, votre matériel…">{{ old('bio', $profile->bio) }}</textarea>
                </div>
                <div class="fields" style="margin-top: 16px">
                    <div class="field">
                        <label for="website">Site web</label>
                        <input id="website" name="website" type="url" value="{{ old('website', $profile->website) }}" placeholder="https://">
                    </div>
                    <div class="field">
                        <label for="instagram">Instagram</label>
                        <input id="instagram" name="instagram" value="{{ old('instagram', $profile->instagram) }}" placeholder="@pseudo">
                    </div>
                    <div class="field">
                        <label for="soundcloud">SoundCloud</label>
                        <input id="soundcloud" name="soundcloud" type="url" value="{{ old('soundcloud', $profile->soundcloud) }}" placeholder="https://soundcloud.com/…">
                    </div>
                </div>
            </div>

            <aside class="panel">
                <p class="panel-title">Photo</p>
                <x-avatar :profile="$profile" size="140" />
                <div class="field" style="margin-top: 14px">
                    <label for="avatar">Image <span>4 Mo max.</span></label>
                    <input id="avatar" type="file" name="avatar" accept="image/*">
                </div>
            </aside>
        </div>

        <div class="panel" style="margin-top: 24px">
            <p class="panel-title">Coordonnées de versement <span class="badge badge-muted">privé</span></p>
            <div class="row" style="gap: 24px; margin-bottom: 16px">
                <label class="check"><input type="radio" name="payout_method" value="paypal" @checked(old('payout_method', $profile->payout_method) === 'paypal')> <span>PayPal</span></label>
                <label class="check"><input type="radio" name="payout_method" value="bank" @checked(old('payout_method', $profile->payout_method) === 'bank')> <span>Virement bancaire</span></label>
            </div>
            <div data-show-when="payout_method=paypal">
                <div class="field" style="max-width: 420px">
                    <label for="payout_email">E-mail du compte PayPal</label>
                    <input id="payout_email" name="payout_email" type="email" value="{{ old('payout_email', $profile->payout_email) }}">
                </div>
            </div>
            <div data-show-when="payout_method=bank">
                <div class="fields">
                    <div class="field">
                        <label for="payout_holder">Titulaire du compte</label>
                        <input id="payout_holder" name="payout_holder" value="{{ old('payout_holder', $profile->payout_holder) }}" maxlength="120">
                    </div>
                    <div class="field">
                        <label for="payout_iban">IBAN <span>{{ $profile->payout_iban ? 'enregistré (chiffré) — laisser vide pour conserver' : 'chiffré en base' }}</span></label>
                        <input id="payout_iban" name="payout_iban" autocomplete="off" placeholder="{{ $profile->payout_iban ? '•••• '.substr($profile->payout_iban, -4) : 'FR76 …' }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions"><button class="btn btn-primary btn-lg">Enregistrer le profil</button></div>
    </form>
@endsection
