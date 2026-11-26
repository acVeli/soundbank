<?php

namespace Tests;

use App\Models\ArtistProfile;
use App\Models\Category;
use App\Models\Pack;
use App\Models\Sample;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function seedCategories(): void
    {
        $this->seed(CategorySeeder::class);
    }

    protected function artist(string $stageName = 'Nova Lux'): User
    {
        $user = User::factory()->create(['role' => User::ROLE_ARTIST]);
        $user->artistProfile()->create([
            'stage_name' => $stageName,
            'slug' => ArtistProfile::uniqueSlug($stageName),
            'payout_method' => 'paypal',
            'payout_email' => 'payout-'.$user->id.'@example.com',
        ]);

        return $user->refresh();
    }

    protected function buyer(): User
    {
        return User::factory()->create(['role' => User::ROLE_BUYER]);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function sample(User $artist, array $attributes = []): Sample
    {
        $path = 'samples/'.$artist->id.'/'.Str::uuid().'.wav';
        Storage::disk('local')->put($path, $this->wavBytes());

        return Sample::query()->create([
            'artist_id' => $artist->id,
            'category_id' => Category::query()->whereNotNull('parent_id')->value('id'),
            'title' => 'Sample '.Str::random(5),
            'slug' => 'sample-'.Str::lower(Str::random(10)),
            'type' => 'one_shot',
            'file_path' => $path,
            'file_name' => 'kick.wav',
            'file_format' => 'wav',
            'file_size' => 15,
            'preview_path' => 'previews/'.$artist->id.'/p.wav',
            'waveform' => [0.1, 0.5, 1],
            'price_cents' => 300,
            'status' => Sample::STATUS_PUBLISHED,
            'published_at' => now(),
            ...$attributes,
        ]);
    }

    /**
     * Petit WAV valide (8 bits mono). Taille impaire par défaut pour éprouver le bourrage.
     */
    protected function wavBytes(string $data = "\x80\x90\xA0"): string
    {
        return 'RIFF'.pack('V', 36 + strlen($data)).'WAVE'
            .'fmt '.pack('VvvVVvv', 16, 1, 1, 8000, 8000, 1, 8)
            .'data'.pack('V', strlen($data)).$data;
    }

    protected function pack(User $artist, int $priceCents = 1000): Pack
    {
        return Pack::query()->create([
            'artist_id' => $artist->id,
            'title' => 'Pack '.Str::random(4),
            'slug' => 'pack-'.Str::lower(Str::random(8)),
            'price_cents' => $priceCents,
            'status' => Pack::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }
}
