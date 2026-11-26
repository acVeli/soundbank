<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Arborescence initiale des sons. Relançable sans doublon (clé : slug).
 */
class CategorySeeder extends Seeder
{
    /**
     * @var array<string, array{description: string, icon: string, children: list<string>}>
     */
    public const TREE = [
        'Vocals' => [
            'description' => 'Voix enregistrées par des vocalistes : phrases, chops, adlibs, harmonies.',
            'icon' => 'mic',
            'children' => ['Female Vocals', 'Male Vocals', 'Vocal Chops', 'Adlibs', 'Choirs & Harmonies', 'Spoken Word'],
        ],
        'Drums' => [
            'description' => 'One-shots et boucles de batterie.',
            'icon' => 'drum',
            'children' => ['Kicks', 'Snares', 'Claps', 'Hi-Hats', 'Open Hats', 'Cymbals & Crashes', 'Percussion', 'Drum Loops', 'Fills'],
        ],
        'Bass' => [
            'description' => '808, sub, basses synthé et boucles de basse.',
            'icon' => 'bass',
            'children' => ['808s', 'Sub Bass', 'Synth Bass', 'Bass Loops'],
        ],
        'Synths' => [
            'description' => 'Leads, nappes, plucks, arpèges et accords.',
            'icon' => 'synth',
            'children' => ['Leads', 'Pads', 'Plucks', 'Arps', 'Chords'],
        ],
        'Instruments' => [
            'description' => 'Instruments joués : claviers, guitares, cordes, cuivres.',
            'icon' => 'keys',
            'children' => ['Keys & Piano', 'Guitars', 'Strings', 'Brass & Winds'],
        ],
        'FX' => [
            'description' => 'Transitions, impacts, textures et bruitages.',
            'icon' => 'fx',
            'children' => ['Risers & Sweeps', 'Impacts & Downlifters', 'Textures & Ambiences', 'Foley'],
        ],
    ];

    public function run(): void
    {
        $familyPosition = 0;

        foreach (self::TREE as $name => $family) {
            $parent = Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $family['description'], 'icon' => $family['icon'], 'position' => $familyPosition++, 'parent_id' => null],
            );

            foreach ($family['children'] as $position => $child) {
                Category::query()->updateOrCreate(
                    ['slug' => Str::slug($name.' '.$child)],
                    ['name' => $child, 'parent_id' => $parent->getKey(), 'position' => $position],
                );
            }
        }
    }
}
