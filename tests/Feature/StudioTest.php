<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LibraryItem;
use App\Models\Pack;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->seedCategories();
    }

    public function test_artist_registration_opens_the_studio(): void
    {
        $this->post(route('register.artist.store'), [
            'stage_name' => 'KRVSH',
            'name' => 'Karim',
            'email' => 'karim@example.com',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'terms' => '1',
        ])->assertRedirect(route('studio.samples.create'));

        $user = User::query()->where('email', 'karim@example.com')->firstOrFail();
        $this->assertTrue($user->isArtist());
        $this->assertSame('krvsh', $user->artistProfile->slug);
        $this->get(route('studio.dashboard'))->assertOk();
        $this->get(route('studio.samples.create'))->assertOk()->assertSee('Female Vocals');
    }

    public function test_buyers_cannot_enter_the_studio_but_can_become_artists(): void
    {
        $buyer = $this->buyer();

        $this->actingAs($buyer)->get(route('studio.dashboard'))->assertRedirect(route('account.become-artist'));
        $this->post(route('account.become-artist.store'), ['stage_name' => 'Sam Beats', 'terms' => '1'])
            ->assertRedirect(route('studio.samples.create'));

        $this->assertTrue($buyer->fresh()->isArtist());
    }

    public function test_upload_stores_original_privately_and_preview_publicly(): void
    {
        $artist = $this->artist();
        $pack = $this->pack($artist);
        $category = Category::query()->where('name', 'Female Vocals')->firstOrFail();

        $response = $this->actingAs($artist)->postJson(route('studio.samples.store'), [
            'file' => UploadedFile::fake()->create('Neon Aah 120bpm Am.wav', 500, 'audio/wav'),
            'preview' => UploadedFile::fake()->create('preview.wav', 100, 'audio/wav'),
            'waveform' => json_encode([0.2, 0.9, 1.4, -1]),
            'duration_ms' => 4000,
            'title' => 'Neon Aah',
            'category_id' => $category->id,
            'pack_id' => $pack->id,
            'type' => 'loop',
            'bpm' => 120,
            'musical_key' => 'Am',
            'tags' => 'Airy, POP, airy',
            'price' => '3,99',
            'publish' => '1',
        ]);

        $response->assertCreated()->assertJson(['status' => 'published']);

        $sample = Sample::query()->firstOrFail();
        $this->assertSame(399, $sample->price_cents);
        $this->assertSame('airy, pop', $sample->tags);
        $this->assertEquals([0.2, 0.9, 1, 0], $sample->waveform);
        $this->assertSame($pack->id, $sample->pack_id);
        Storage::disk('local')->assertExists($sample->file_path);
        Storage::disk('public')->assertExists($sample->preview_path);
        $this->assertStringStartsWith('samples/'.$artist->id.'/', $sample->file_path);
    }

    public function test_upload_rejects_non_audio_and_foreign_packs(): void
    {
        $artist = $this->artist();
        $foreignPack = $this->pack($this->artist('Other'));

        $this->actingAs($artist)->postJson(route('studio.samples.store'), [
            'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
            'title' => 'Bad',
            'type' => 'one_shot',
            'price' => 2,
            'pack_id' => $foreignPack->id,
        ])->assertUnprocessable()->assertJsonValidationErrors(['file', 'pack_id']);
    }

    public function test_artist_cannot_touch_someone_elses_sample(): void
    {
        $sample = $this->sample($this->artist('Owner'));

        $this->actingAs($this->artist('Intruder'))
            ->get(route('studio.samples.edit', $sample))->assertNotFound();
        $this->delete(route('studio.samples.destroy', $sample))->assertNotFound();
        $this->assertNotNull($sample->fresh());
    }

    public function test_bulk_publish_skips_samples_without_category(): void
    {
        $artist = $this->artist();
        $ready = $this->sample($artist, ['status' => Sample::STATUS_DRAFT, 'published_at' => null]);
        $incomplete = $this->sample($artist, ['status' => Sample::STATUS_DRAFT, 'category_id' => null]);

        $this->actingAs($artist)->post(route('studio.samples.bulk'), [
            'action' => 'publish',
            'ids' => [$ready->id, $incomplete->id],
        ])->assertRedirect();

        $this->assertSame(Sample::STATUS_PUBLISHED, $ready->fresh()->status);
        $this->assertNotNull($ready->fresh()->published_at);
        $this->assertSame(Sample::STATUS_DRAFT, $incomplete->fresh()->status);
    }

    public function test_deleting_a_sold_sample_keeps_it_for_buyers(): void
    {
        $artist = $this->artist();
        $sold = $this->sample($artist);
        $unsold = $this->sample($artist);
        LibraryItem::query()->create(['user_id' => $this->buyer()->id, 'sample_id' => $sold->id, 'license_key' => 'SBL-TEST']);

        $this->actingAs($artist)->delete(route('studio.samples.destroy', $sold))->assertRedirect();
        $this->delete(route('studio.samples.destroy', $unsold))->assertRedirect();

        $this->assertSoftDeleted($sold);
        Storage::disk('local')->assertExists($sold->file_path);
        $this->assertDatabaseMissing('samples', ['id' => $unsold->id]);
        Storage::disk('local')->assertMissing($unsold->file_path);
    }

    public function test_pack_lifecycle(): void
    {
        $artist = $this->artist();

        $this->actingAs($artist)->post(route('studio.packs.store'), [
            'title' => 'Neon Vocals',
            'price' => '19.99',
            'genre' => 'Pop',
        ])->assertRedirect();

        $pack = Pack::query()->firstOrFail();
        $this->assertSame(1999, $pack->price_cents);
        $this->assertSame(Pack::STATUS_DRAFT, $pack->status);

        $this->put(route('studio.packs.update', $pack), ['title' => 'Neon Vocals', 'price' => '19.99', 'publish' => '1'])
            ->assertSessionHasErrors('publish');

        $this->sample($artist, ['pack_id' => $pack->id]);
        $this->put(route('studio.packs.update', $pack), ['title' => 'Neon Vocals', 'price' => '19.99', 'publish' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertTrue($pack->fresh()->isPublished());

        $this->get(route('studio.packs.index'))->assertOk()->assertSee('Neon Vocals');
    }
}
