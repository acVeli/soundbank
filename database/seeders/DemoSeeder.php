<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Pack;
use App\Models\Sample;
use App\Models\User;
use App\Services\Payments\OrderFulfiller;
use App\Services\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Données de démonstration (environnement local uniquement) : un admin, trois
 * artistes avec packs et sons réellement écoutables, un acheteur avec une
 * commande payée (bibliothèque + facture).
 *
 * Comptes de démo (mot de passe commun : « password ») :
 * - admin@soundbank.test   — administration
 * - nova@soundbank.test    — artiste (voix)
 * - krvsh@soundbank.test   — artiste (drums)
 * - orion@soundbank.test   — artiste (basses / synthés)
 * - buyer@soundbank.test   — acheteur
 */
class DemoSeeder extends Seeder
{
    private const PASSWORD = 'password';

    public function run(DemoSynth $synth, Settings $settings, OrderFulfiller $fulfiller): void
    {
        User::query()->updateOrCreate(['email' => 'admin@soundbank.test'], [
            'name' => 'Admin Soundbank',
            'password' => self::PASSWORD,
            'role' => User::ROLE_BUYER,
        ])->forceFill(['is_admin' => true])->save();

        $settings->put([
            'seller_name' => 'Soundbank (démo)',
            'seller_address' => "1 rue de la Grille\n75000 Paris",
            'seller_siret' => '000 000 000 00000',
            'seller_email' => 'contact@soundbank.test',
        ]);

        $catalog = [
            [
                'email' => 'nova@soundbank.test', 'name' => 'Léa Martin', 'stage' => 'NOVA LUX', 'verified' => true,
                'location' => 'Paris', 'bio' => 'Topliner et vocaliste. Voix aériennes, harmonies et chops pour la pop, l’afro et la trap mélodique.',
                'pack' => ['Neon Vocals Vol. 1', 'Pop', 2499, 'Phrases, « aah », « ooh » et chops enregistrés en studio, prêts à poser sur vos prods.'],
                'samples' => [
                    ['Neon Aah 120 Am', 'vocal_female', 'Female Vocals', 'loop', 120, 'Am', 399, 'airy, pop, female'],
                    ['Halo Ooh Phrase', 'vocal_female_oh', 'Female Vocals', 'loop', 100, 'A', 399, 'soft, ooh, female'],
                    ['Glass Chops 128', 'vocal_chop', 'Vocal Chops', 'loop', 128, 'C', 299, 'chop, edm, bright'],
                    ['Deep Oh Male Guest', 'vocal_male', 'Male Vocals', 'loop', 90, 'C', 399, 'male, choir, deep'],
                ],
            ],
            [
                'email' => 'krvsh@soundbank.test', 'name' => 'Karim Sh.', 'stage' => 'KRVSH', 'verified' => true,
                'location' => 'Marseille', 'bio' => 'Producteur trap / drill. Des drums qui claquent, sans traitement inutile.',
                'pack' => ['Grid Drums', 'Trap', 1499, 'Kicks, snares, claps et hats taillés pour la trap et la drill.'],
                'samples' => [
                    ['Grid Kick 01', 'kick', 'Kicks', 'one_shot', null, null, 149, 'punchy, trap'],
                    ['Grid Snare 01', 'snare', 'Snares', 'one_shot', null, null, 149, 'crisp, trap'],
                    ['Grid Clap 01', 'clap', 'Claps', 'one_shot', null, null, 149, 'wide, clap'],
                    ['Laser Hat Closed', 'hat', 'Hi-Hats', 'one_shot', null, null, 99, 'tight, hat'],
                    ['Laser Hat Open', 'open_hat', 'Open Hats', 'one_shot', null, null, 99, 'open, hat'],
                    ['Lightcycle Groove 140', 'drum_loop', 'Drum Loops', 'loop', 140, null, 299, 'groove, trap, loop'],
                ],
            ],
            [
                'email' => 'orion@soundbank.test', 'name' => 'Orion Blanc', 'stage' => 'Orion Bass', 'verified' => false,
                'location' => 'Lyon', 'bio' => 'Basses profondes et nappes synthétiques inspirées des années 80.',
                'pack' => ['Sub Circuit', 'Techno', 1999, 'Des 808 aux pads analogiques : le bas du spectre, en couleur.'],
                'samples' => [
                    ['Circuit 808 A', '808', '808s', 'one_shot', null, 'A', 199, '808, long, distorted'],
                    ['Pure Sub E', 'sub', 'Sub Bass', 'one_shot', null, 'E', 199, 'sub, clean'],
                    ['Mainframe Bassline 124', 'bass_loop', 'Bass Loops', 'loop', 124, 'Am', 299, 'bassline, techno'],
                    ['Arcade Pad Am', 'pad', 'Pads', 'one_shot', null, 'Am', 249, 'pad, warm, 80s'],
                    ['Data Pluck 124', 'pluck', 'Plucks', 'loop', 124, 'C', 249, 'pluck, arp'],
                    ['Disc Riser 4s', 'riser', 'Risers & Sweeps', 'one_shot', null, null, 149, 'riser, build'],
                    ['Recognizer Impact', 'impact', 'Impacts & Downlifters', 'one_shot', null, null, 149, 'impact, boom'],
                ],
            ],
        ];

        $categories = Category::query()->whereNotNull('parent_id')->pluck('id', 'name');
        $samples = collect();

        foreach ($catalog as $entry) {
            $artist = User::query()->updateOrCreate(['email' => $entry['email']], [
                'name' => $entry['name'],
                'password' => self::PASSWORD,
                'role' => User::ROLE_ARTIST,
            ]);

            $artist->artistProfile()->updateOrCreate([], [
                'stage_name' => $entry['stage'],
                'slug' => Str::slug($entry['stage']),
                'bio' => $entry['bio'],
                'location' => $entry['location'],
                'is_verified' => $entry['verified'],
                'payout_method' => 'paypal',
                'payout_email' => $entry['email'],
            ]);

            [$packTitle, $genre, $packPrice, $description] = $entry['pack'];
            $pack = Pack::query()->updateOrCreate(['slug' => Str::slug($entry['stage'].' '.$packTitle)], [
                'artist_id' => $artist->getKey(),
                'title' => $packTitle,
                'genre' => $genre,
                'price_cents' => $packPrice,
                'description' => $description,
                'status' => Pack::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);

            foreach ($entry['samples'] as [$title, $sound, $category, $type, $bpm, $key, $price, $tags]) {
                $slug = Str::slug($entry['stage'].' '.$title);

                if ($existing = Sample::query()->where('slug', $slug)->first()) {
                    $samples->push($existing);

                    continue;
                }

                $audio = $synth->render($sound, (float) ($bpm ?? 120));
                $original = 'samples/'.$artist->getKey().'/'.Str::uuid().'.wav';
                $preview = 'previews/'.$artist->getKey().'/'.Str::random(32).'.wav';
                $bytes = $synth->wav($audio);
                Storage::disk('local')->put($original, $bytes);
                Storage::disk('public')->put($preview, $synth->wav($synth->preview($audio), DemoSynth::RATE / 2));

                $samples->push(Sample::query()->create([
                    'artist_id' => $artist->getKey(),
                    'pack_id' => $pack->getKey(),
                    'category_id' => $categories[$category] ?? null,
                    'title' => $title,
                    'slug' => $slug,
                    'type' => $type,
                    'bpm' => $bpm,
                    'musical_key' => $key,
                    'duration_ms' => (int) round(count($audio) / DemoSynth::RATE * 1000),
                    'tags' => $tags,
                    'file_path' => $original,
                    'file_name' => Str::slug($title, '_').'.wav',
                    'file_format' => 'wav',
                    'file_size' => strlen($bytes),
                    'preview_path' => $preview,
                    'waveform' => $synth->peaks($audio),
                    'price_cents' => $price,
                    'status' => Sample::STATUS_PUBLISHED,
                    'published_at' => now()->subMinutes(random_int(1, 5000)),
                    'plays_count' => random_int(10, 900),
                ]));
            }
        }

        $buyer = User::query()->updateOrCreate(['email' => 'buyer@soundbank.test'], [
            'name' => 'Sam Producer',
            'password' => self::PASSWORD,
            'role' => User::ROLE_BUYER,
        ]);

        if ($buyer->orders()->exists()) {
            return;
        }

        $bought = $samples->filter(fn (Sample $s): bool => in_array($s->title, ['Neon Aah 120 Am', 'Grid Kick 01', 'Circuit 808 A'], true));
        $commission = $settings->commissionBasisPoints();

        $order = Order::query()->create([
            'user_id' => $buyer->getKey(),
            'status' => Order::STATUS_PENDING,
            'provider' => 'stripe',
            'reference' => 'cs_demo_'.Str::random(12),
            'total_cents' => (int) $bought->sum('price_cents'),
            'currency' => 'EUR',
            'billing_name' => $buyer->name,
            'billing_email' => $buyer->email,
        ]);

        foreach ($bought as $sample) {
            $order->items()->create([
                'item_type' => OrderItem::TYPE_SAMPLE,
                'sample_id' => $sample->getKey(),
                'artist_id' => $sample->artist_id,
                'title' => $sample->title,
                'artist_name' => $sample->artist->displayName(),
                'price_cents' => $sample->price_cents,
                'commission_bp' => $commission,
                'artist_earning_cents' => $sample->price_cents - (int) round($sample->price_cents * $commission / 10000),
            ]);
        }

        $fulfiller->fulfill($order);
        $buyer->favorites()->syncWithoutDetaching($samples->take(4)->pluck('id'));
    }
}
