<?php

namespace Tests\Feature;

use App\Mail\InvoiceIssued;
use App\Models\Invoice;
use App\Models\LibraryItem;
use App\Models\Order;
use App\Models\Payout;
use App\Services\ArtistLedger;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Mail::fake();
        $this->seedCategories();

        app(Settings::class)->put([
            'stripe_enabled' => '1',
            'stripe_secret_key' => 'sk_test_123',
            'stripe_webhook_secret' => 'whsec_test',
            'commission_percent' => '30',
            'payout_min_cents' => '500',
        ]);
    }

    public function test_cart_rules(): void
    {
        $artist = $this->artist();
        $pack = $this->pack($artist);
        $inPack = $this->sample($artist, ['pack_id' => $pack->id]);
        $buyer = $this->buyer();

        $this->actingAs($buyer)->postJson(route('cart.add', ['type' => 'sample', 'id' => $inPack->id]))->assertOk()->assertJson(['count' => 1]);
        $this->postJson(route('cart.add', ['type' => 'pack', 'id' => $pack->id]))->assertOk()->assertJson(['count' => 1]);
        $this->postJson(route('cart.add', ['type' => 'sample', 'id' => $inPack->id]))->assertUnprocessable();

        $this->actingAs($artist)->postJson(route('cart.add', ['type' => 'sample', 'id' => $inPack->id]))->assertUnprocessable();
    }

    public function test_stripe_purchase_delivers_library_invoice_and_artist_share(): void
    {
        $artist = $this->artist();
        $sampleA = $this->sample($artist, ['price_cents' => 300]);
        $pack = $this->pack($artist, 1000);
        $packSample = $this->sample($artist, ['pack_id' => $pack->id, 'price_cents' => 500]);
        $buyer = $this->buyer();

        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_1', 'url' => 'https://checkout.stripe.com/pay/cs_test_1']),
        ]);

        $this->actingAs($buyer)->post(route('cart.add', ['type' => 'sample', 'id' => $sampleA->id]));
        $this->post(route('cart.add', ['type' => 'pack', 'id' => $pack->id]));
        $this->get(route('checkout'))->assertOk()->assertSee('Payer par carte');

        $this->post(route('checkout.store', 'stripe'), [
            'billing_name' => 'Sam Producer',
            'billing_email' => 'sam@example.com',
            'accept_license' => '1',
        ])->assertRedirect('https://checkout.stripe.com/pay/cs_test_1');

        $order = Order::query()->with('items')->firstOrFail();
        $this->assertSame(1300, $order->total_cents);
        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame(210, $order->items->firstWhere('item_type', 'sample')->artist_earning_cents);
        $this->assertSame(700, $order->items->firstWhere('item_type', 'pack')->artist_earning_cents);

        Http::fake([
            'api.stripe.com/v1/checkout/sessions/*' => Http::response([
                'payment_status' => 'paid',
                'client_reference_id' => (string) $order->id,
                'amount_total' => 1300,
                'currency' => 'eur',
            ]),
        ]);

        $this->get(route('checkout.stripe.return', ['order' => $order, 'session_id' => 'cs_test_1']))
            ->assertRedirect(route('checkout.success', $order));

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertEqualsCanonicalizing([$sampleA->id, $packSample->id], $buyer->ownedSampleIds());

        $invoice = Invoice::query()->firstOrFail();
        $this->assertSame('F-'.now()->year.'-000001', $invoice->number);
        $this->assertCount(2, $invoice->lines);
        $this->assertSame(1300, $invoice->total_ttc_cents);
        Mail::assertSent(InvoiceIssued::class, fn ($mail) => $mail->hasTo('sam@example.com'));

        $this->assertSame(910, app(ArtistLedger::class)->availableCents($artist));
        $this->assertSame(1, $sampleA->fresh()->sales_count);
        $this->assertSame(1, $pack->fresh()->sales_count);

        $this->get(route('checkout.success', $order))->assertOk();
        $this->get(route('account.library'))->assertOk()->assertSee($sampleA->title);
        $this->get(route('invoices.show', $invoice->access_token))->assertOk()->assertSee($invoice->number);

        $item = LibraryItem::query()->where('sample_id', $sampleA->id)->firstOrFail();
        $this->get(route('account.library.download', $item))->assertOk()->assertDownload();
        $this->assertSame(1, $item->fresh()->download_count);
        $this->get(route('account.library.license', $item))->assertOk()->assertSee($item->license_key);

        $zip = $this->get(route('account.orders.zip', $order));
        $zip->assertOk();
        $this->assertStringStartsWith("PK\x03\x04", $zip->streamedContent());

        // Un retour rejoué ne crée ni deuxième facture ni double crédit.
        $this->get(route('checkout.stripe.return', ['order' => $order, 'session_id' => 'cs_test_1']));
        $this->assertSame(1, Invoice::query()->count());
        $this->assertSame(1, $sampleA->fresh()->sales_count);
    }

    public function test_other_users_cannot_access_orders_or_downloads(): void
    {
        $artist = $this->artist();
        $sample = $this->sample($artist);
        $owner = $this->buyer();
        $item = LibraryItem::query()->create(['user_id' => $owner->id, 'sample_id' => $sample->id, 'license_key' => 'SBL-X']);

        $this->actingAs($this->buyer())->get(route('account.library.download', $item))->assertNotFound();
        $this->get(route('account.library.license', $item))->assertNotFound();
    }

    public function test_stripe_webhook_requires_a_valid_signature(): void
    {
        $artist = $this->artist();
        $sample = $this->sample($artist);
        $buyer = $this->buyer();
        $order = Order::query()->create([
            'user_id' => $buyer->id, 'status' => Order::STATUS_PENDING, 'provider' => 'stripe',
            'reference' => 'cs_hook', 'total_cents' => 300, 'currency' => 'EUR', 'billing_email' => 'b@example.com',
        ]);
        $order->items()->create([
            'item_type' => 'sample', 'sample_id' => $sample->id, 'artist_id' => $artist->id, 'title' => $sample->title,
            'price_cents' => 300, 'commission_bp' => 3000, 'artist_earning_cents' => 210,
        ]);

        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => [
            'id' => 'cs_hook', 'payment_status' => 'paid', 'client_reference_id' => (string) $order->id,
            'amount_total' => 300, 'currency' => 'eur',
        ]]]);

        $this->call('POST', route('webhooks.stripe'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => 't='.time().',v1=bad'], $payload)->assertStatus(400);
        $this->assertFalse($order->fresh()->isPaid());

        $time = time();
        $signature = hash_hmac('sha256', $time.'.'.$payload, 'whsec_test');
        $this->call('POST', route('webhooks.stripe'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => "t={$time},v1={$signature}"], $payload)->assertOk();

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame([$sample->id], $buyer->ownedSampleIds());
    }

    public function test_payout_requests_respect_balance_and_minimum(): void
    {
        $artist = $this->artist();
        $sample = $this->sample($artist, ['price_cents' => 1000]);
        $order = Order::query()->create(['user_id' => $this->buyer()->id, 'status' => Order::STATUS_PAID, 'total_cents' => 1000, 'paid_at' => now()]);
        $order->items()->create([
            'item_type' => 'sample', 'sample_id' => $sample->id, 'artist_id' => $artist->id, 'title' => 'x',
            'price_cents' => 1000, 'commission_bp' => 3000, 'artist_earning_cents' => 700,
        ]);

        $this->actingAs($artist)->post(route('studio.payouts.store'), ['amount' => '4'])->assertSessionHas('error');
        $this->post(route('studio.payouts.store'), ['amount' => '8'])->assertSessionHas('error');
        $this->post(route('studio.payouts.store'), ['amount' => '7'])->assertSessionHas('status');

        $payout = Payout::query()->firstOrFail();
        $this->assertSame(700, $payout->amount_cents);
        $this->assertSame(0, app(ArtistLedger::class)->availableCents($artist));

        $this->actingAs($this->admin())->patch(route('admin.payouts.update', $payout), ['decision' => 'rejected'])->assertRedirect();
        $this->assertSame(700, app(ArtistLedger::class)->availableCents($artist));
    }
}
