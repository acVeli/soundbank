<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->seedCategories();
    }

    public function test_public_pages_render(): void
    {
        $artist = $this->artist();
        $pack = $this->pack($artist);
        $sample = $this->sample($artist, ['title' => 'Neon Aah', 'pack_id' => $pack->id]);

        $this->get('/')->assertOk()->assertSee('Neon Aah');
        $this->get(route('explore'))->assertOk()->assertSee('Neon Aah');
        $this->get(route('samples.show', $sample))->assertOk()->assertSee('Nova Lux');
        $this->get(route('packs.index'))->assertOk()->assertSee($pack->title);
        $this->get(route('packs.show', $pack))->assertOk()->assertSee('Neon Aah');
        $this->get(route('artists.index'))->assertOk()->assertSee('Nova Lux');
        $this->get(route('artists.show', $artist->artistProfile))->assertOk();
    }

    public function test_category_filter_includes_subcategories(): void
    {
        $artist = $this->artist();
        $vocals = Category::query()->where('slug', 'vocals')->firstOrFail();
        $female = $vocals->children()->where('name', 'Female Vocals')->firstOrFail();
        $kicks = Category::query()->where('name', 'Kicks')->firstOrFail();

        $this->sample($artist, ['title' => 'Voix Aerienne', 'category_id' => $female->id]);
        $this->sample($artist, ['title' => 'Gros Kick', 'category_id' => $kicks->id]);

        $this->get(route('explore.category', $vocals))->assertOk()->assertSee('Voix Aerienne')->assertDontSee('Gros Kick');
        $this->get(route('explore', ['q' => 'kick']))->assertSee('Gros Kick')->assertDontSee('Voix Aerienne');
    }

    public function test_drafts_blocked_and_suspended_artists_are_hidden(): void
    {
        $artist = $this->artist();
        $draft = $this->sample($artist, ['title' => 'Brouillon Secret', 'status' => Sample::STATUS_DRAFT]);
        $blocked = $this->sample($artist, ['title' => 'Son Bloque', 'status' => Sample::STATUS_BLOCKED]);

        $this->get(route('explore'))->assertDontSee('Brouillon Secret')->assertDontSee('Son Bloque');
        $this->get(route('samples.show', $draft))->assertNotFound();
        $this->actingAs($artist)->get(route('samples.show', $draft))->assertOk();

        $other = $this->artist('Hidden');
        $visible = $this->sample($other, ['title' => 'Bientot Cache']);
        $other->update(['status' => User::STATUS_SUSPENDED]);
        auth()->logout();

        $this->get(route('explore'))->assertDontSee('Bientot Cache');
        $this->get(route('samples.show', $visible))->assertNotFound();
        $this->assertNotNull($blocked);
    }

    public function test_play_counter(): void
    {
        $sample = $this->sample($this->artist());

        $this->post(route('samples.play', $sample))->assertNoContent();
        $this->assertSame(1, $sample->fresh()->plays_count);
    }
}
