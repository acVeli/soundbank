<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/*
| Crée (ou promeut) un compte administrateur : php artisan soundbank:admin email@domaine.fr
*/
Artisan::command('soundbank:admin {email} {--name=Administrateur}', function (string $email): int {
    $user = User::query()->firstWhere('email', $email);
    $password = null;

    if ($user === null) {
        $password = $this->secret('Mot de passe (laisser vide pour en générer un)') ?: Str::password(16);
        $user = User::query()->create([
            'name' => (string) $this->option('name'),
            'email' => $email,
            'password' => $password,
        ]);
    }

    $user->forceFill(['is_admin' => true])->save();
    $this->info("{$email} est administrateur.");

    if ($password !== null) {
        $this->line('Mot de passe : '.$password);
    }

    return 0;
})->purpose('Créer ou promouvoir un administrateur Soundbank');
