<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\ArtistProfile;
use App\Services\SampleStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Profil public de l'artiste et coordonnées de versement.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('studio.profile', ['profile' => $request->user()->artistProfile]);
    }

    public function update(Request $request, SampleStorage $storage): RedirectResponse
    {
        $profile = $request->user()->artistProfile;

        $data = $request->validate([
            'stage_name' => ['required', 'string', 'min:2', 'max:80'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:80'],
            'website' => ['nullable', 'url', 'max:190'],
            'instagram' => ['nullable', 'string', 'max:80', 'regex:/^@?[A-Za-z0-9._]+$/'],
            'soundcloud' => ['nullable', 'url', 'max:190'],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'payout_method' => ['required', 'in:paypal,bank'],
            'payout_email' => ['nullable', 'required_if:payout_method,paypal', 'email', 'max:190'],
            'payout_holder' => ['nullable', 'required_if:payout_method,bank', 'string', 'max:120'],
            'payout_iban' => ['nullable', 'string', 'max:40', 'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9 ]{10,32}$/i'],
        ], [
            'payout_email.required_if' => 'Indiquez l’adresse de votre compte PayPal.',
            'payout_holder.required_if' => 'Indiquez le titulaire du compte bancaire.',
            'payout_iban.regex' => 'IBAN invalide.',
            'instagram.regex' => 'Identifiant Instagram invalide.',
        ]);

        if ($data['payout_method'] === 'bank' && blank($data['payout_iban'] ?? null) && blank($profile->payout_iban)) {
            return back()->withInput()->withErrors(['payout_iban' => 'Indiquez votre IBAN.']);
        }

        if ($request->hasFile('avatar')) {
            if ($profile->avatar_path) {
                Storage::disk('public')->delete($profile->avatar_path);
            }

            $profile->avatar_path = $storage->storeImage($request->file('avatar'), 'avatars');
        }

        $stageName = trim($data['stage_name']);

        $profile->fill([
            'stage_name' => $stageName,
            'slug' => $stageName !== $profile->stage_name ? ArtistProfile::uniqueSlug($stageName, $profile->getKey()) : $profile->slug,
            'bio' => $data['bio'] ?? null,
            'location' => $data['location'] ?? null,
            'website' => $data['website'] ?? null,
            'instagram' => filled($data['instagram'] ?? null) ? ltrim($data['instagram'], '@') : null,
            'soundcloud' => $data['soundcloud'] ?? null,
            'payout_method' => $data['payout_method'],
            'payout_email' => $data['payout_email'] ?? null,
            'payout_holder' => $data['payout_holder'] ?? null,
        ]);

        if (filled($data['payout_iban'] ?? null)) {
            $profile->payout_iban = strtoupper(preg_replace('/\s+/', '', $data['payout_iban']));
        }

        $profile->save();

        return back()->with('status', 'Profil enregistré.');
    }
}
