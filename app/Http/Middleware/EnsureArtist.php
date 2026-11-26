<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Le studio est réservé aux comptes artistes actifs, avec un profil.
 */
class EnsureArtist
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isArtist() || $user->artistProfile === null) {
            return redirect()->route('account.become-artist')
                ->with('status', 'Créez votre profil artiste pour accéder au studio.');
        }

        if (! $user->isActive()) {
            abort(403, 'Votre compte artiste est suspendu. Contactez l’équipe.');
        }

        return $next($request);
    }
}
