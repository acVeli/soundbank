<?php

use App\Models\LibraryItem;
use App\Models\User;
use App\Services\AudioWatermark;
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

/*
| Identifie l'acheteur d'un fichier retrouvé en ligne : php artisan soundbank:identify chemin/du/fichier.wav
*/
Artisan::command('soundbank:identify {path}', function (string $path, AudioWatermark $watermark): int {
    if (! is_file($path)) {
        $this->error('Fichier introuvable.');

        return 1;
    }

    $mark = $watermark->read($path);

    if ($mark === null) {
        $this->warn('Aucun filigrane Soundbank : fichier converti, réenregistré, ou non vendu par la plateforme.');

        return 1;
    }

    $item = LibraryItem::query()->with(['user', 'sample', 'orderItem.order'])->where('license_key', $mark['lic'])->first();

    $this->table(['Champ', 'Valeur'], [
        ['Signature', $mark['valid'] ? 'authentique' : 'INVALIDE (filigrane falsifié)'],
        ['Licence', $mark['lic']],
        ['Téléchargé le', date('d/m/Y H:i', (int) ($mark['t'] ?? 0))],
        ['Acheteur', $item ? $item->user->name.' <'.$item->user->email.'> (#'.$item->user_id.')' : 'compte #'.$mark['u'].' (introuvable)'],
        ['Sample', $item?->sample?->title ?? '#'.$mark['s']],
        ['Commande', $item?->orderItem?->order?->number ?? '—'],
    ]);

    return $mark['valid'] ? 0 : 2;
})->purpose('Retrouver l’acheteur d’un fichier Soundbank à partir de son filigrane');
