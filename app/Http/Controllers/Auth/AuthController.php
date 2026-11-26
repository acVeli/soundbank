<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ArtistProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Connexion commune et deux parcours d'inscription : acheteur et artiste.
 */
class AuthController extends Controller
{
    public function login(): View
    {
        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Indiquez votre adresse e-mail.',
            'password.required' => 'Indiquez votre mot de passe.',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->onlyInput('email')->withErrors(['email' => 'Identifiants incorrects.']);
        }

        if (! $request->user()->isActive() && ! $request->user()->is_admin) {
            Auth::logout();

            return back()->onlyInput('email')->withErrors(['email' => 'Ce compte est suspendu.']);
        }

        $request->session()->regenerate();
        $user = $request->user();

        $home = match (true) {
            $user->is_admin => route('admin.dashboard'),
            $user->isArtist() => route('studio.dashboard'),
            default => route('account.dashboard'),
        };

        return redirect()->intended($home);
    }

    public function register(): View
    {
        return view('auth.register', ['artist' => false]);
    }

    public function registerArtist(): View
    {
        return view('auth.register', ['artist' => true]);
    }

    public function storeBuyer(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(), $this->messages());

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => User::ROLE_BUYER,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('explore'))->with('status', 'Bienvenue sur '.config('app.name').' !');
    }

    public function storeArtist(Request $request): RedirectResponse
    {
        $data = $request->validate([
            ...$this->rules(),
            'stage_name' => ['required', 'string', 'min:2', 'max:80'],
        ], [
            ...$this->messages(),
            'stage_name.required' => 'Choisissez votre nom d’artiste.',
        ]);

        $user = DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => User::ROLE_ARTIST,
            ]);

            $user->artistProfile()->create([
                'stage_name' => trim($data['stage_name']),
                'slug' => ArtistProfile::uniqueSlug($data['stage_name']),
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('studio.samples.create')->with('status', 'Votre studio est prêt : importez vos premiers sons.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'name.required' => 'Indiquez votre nom.',
            'email.unique' => 'Un compte existe déjà avec cette adresse.',
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
            'terms.accepted' => 'Vous devez accepter les conditions d’utilisation.',
        ];
    }
}
