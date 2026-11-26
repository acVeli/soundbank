<?php

namespace Tests\Feature;

use App\Models\LibraryItem;
use App\Models\Order;
use App\Models\Sample;
use App\Models\User;
use App\Services\AudioWatermark;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WatermarkTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    private Sample $sample;

    private LibraryItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->seedCategories();

        $this->buyer = User::factory()->create(['name' => 'Sam Producer']);
        $this->sample = $this->sample($this->artist());
        $order = Order::query()->create(['user_id' => $this->buyer->id, 'status' => Order::STATUS_PAID, 'total_cents' => 300, 'paid_at' => now()]);
        $orderItem = $order->items()->create([
            'item_type' => 'sample', 'sample_id' => $this->sample->id, 'artist_id' => $this->sample->artist_id,
            'title' => $this->sample->title, 'price_cents' => 300, 'commission_bp' => 3000, 'artist_earning_cents' => 210,
        ]);
        $this->item = LibraryItem::query()->create([
            'user_id' => $this->buyer->id, 'sample_id' => $this->sample->id,
            'order_item_id' => $orderItem->id, 'license_key' => 'SBL-AAAA-BBBB-CCCC',
        ]);
    }

    public function test_downloaded_wav_carries_the_buyer_license_and_stays_valid(): void
    {
        $response = $this->actingAs($this->buyer)->get(route('account.library.download', $this->item));
        $response->assertOk()->assertDownload();
        $bytes = $response->streamedContent();
        $original = Storage::disk('local')->get($this->sample->file_path);

        $this->assertStringStartsWith(substr($original, 0, 4), $bytes);
        $this->assertSame(strlen($bytes) - 8, unpack('V', substr($bytes, 4, 4))[1], 'La taille RIFF doit couvrir tout le fichier.');
        $this->assertSame(substr($original, 8), substr($bytes, 8, strlen($original) - 8), 'Le son original est recopié à l’identique.');
        $this->assertSame(0, strlen($bytes) % 2);
        $this->assertStringContainsString('LIST', $bytes);
        $this->assertStringContainsString('SBL-AAAA-BBBB-CCCC', $bytes);
        $this->assertStringContainsString('Sam Producer', $bytes);
        $this->assertStringNotContainsString($this->buyer->email, $bytes, 'Pas d’e-mail dans les fichiers distribués.');
        $this->assertSame((string) strlen($bytes), $response->headers->get('Content-Length'));

        $file = tempnam(sys_get_temp_dir(), 'leak');
        file_put_contents($file, $bytes);
        $mark = app(AudioWatermark::class)->read($file);
        @unlink($file);

        $this->assertNotNull($mark);
        $this->assertTrue($mark['valid']);
        $this->assertSame($this->buyer->id, $mark['u']);
    }

    public function test_identify_command_finds_the_buyer_and_detects_forgery(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'leak');
        $out = fopen($file, 'wb');
        app(AudioWatermark::class)->write(Storage::disk('local')->path($this->sample->file_path), $this->item, $out);
        fclose($out);

        $this->artisan('soundbank:identify', ['path' => $file])
            ->expectsOutputToContain('authentique')
            ->expectsOutputToContain($this->buyer->email)
            ->assertExitCode(0);

        file_put_contents($file, preg_replace('/"u":\d+/', '"u":999', (string) file_get_contents($file)));
        $this->artisan('soundbank:identify', ['path' => $file])->expectsOutputToContain('INVALIDE')->assertExitCode(2);

        @unlink($file);
    }

    public function test_aiff_files_get_an_annotation_and_other_formats_are_untouched(): void
    {
        $aiff = 'FORM'.pack('N', 4 + 8 + 4).'AIFF'.'SSND'.pack('N', 4).'abcd';
        Storage::disk('local')->put($this->sample->file_path, $aiff);
        $this->sample->update(['file_format' => 'aiff']);

        $bytes = $this->actingAs($this->buyer)->get(route('account.library.download', $this->item))->streamedContent();
        $this->assertSame(strlen($bytes) - 8, unpack('N', substr($bytes, 4, 4))[1]);
        $this->assertStringContainsString('ANNO', $bytes);
        $this->assertStringContainsString('SBL-AAAA-BBBB-CCCC', $bytes);

        Storage::disk('local')->put($this->sample->file_path, 'ID3-not-a-riff-mp3');
        $this->sample->update(['file_format' => 'mp3']);
        $this->assertSame('ID3-not-a-riff-mp3', $this->get(route('account.library.download', $this->item))->streamedContent());
    }

    public function test_zip_contains_watermarked_files(): void
    {
        $zip = $this->actingAs($this->buyer)->get(route('account.orders.zip', $this->item->orderItem->order))->streamedContent();

        $this->assertStringStartsWith("PK\x03\x04", $zip);
        $this->assertStringContainsString('SBL-AAAA-BBBB-CCCC', $zip);
    }

    public function test_private_disk_has_no_public_route(): void
    {
        $this->assertFalse(Route::has('storage.local'));
    }
}
