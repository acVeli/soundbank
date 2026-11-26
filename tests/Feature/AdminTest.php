<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Sample;
use App\Models\Setting;
use App\Models\User;
use App\Services\InvoiceIssuer;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Mail::fake();
        $this->seedCategories();
    }

    public function test_admin_area_is_restricted(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->actingAs($this->buyer())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->artist())->get(route('admin.settings'))->assertForbidden();
    }

    public function test_every_admin_page_renders(): void
    {
        $this->actingAs($this->admin());

        foreach (['admin.dashboard', 'admin.orders', 'admin.invoices', 'admin.artists', 'admin.samples', 'admin.payouts', 'admin.categories', 'admin.settings'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_settings_are_encrypted_and_secrets_never_echoed(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update'), [
            'stripe_enabled' => '1',
            'stripe_public_key' => 'pk_test_abc',
            'stripe_secret_key' => 'sk_test_SECRET',
            'paypal_mode' => 'sandbox',
            'commission_percent' => '25',
            'payout_min_euros' => '10',
            'vat_rate' => '20',
            'invoice_series' => 'fa',
            'credit_series' => 'av',
        ])->assertRedirect(route('admin.settings'));

        $settings = app(Settings::class);
        $this->assertTrue($settings->stripeEnabled());
        $this->assertSame(2500, $settings->commissionBasisPoints());
        $this->assertSame(1000, $settings->payoutMinCents());
        $this->assertSame('FA', $settings->get('invoice_series'));

        $raw = Setting::query()->toBase()->where('key', 'stripe_secret_key')->value('value');
        $this->assertStringNotContainsString('sk_test_SECRET', (string) $raw);
        $this->get(route('admin.settings'))->assertDontSee('sk_test_SECRET');
    }

    public function test_connection_test_reports_stripe_status(): void
    {
        app(Settings::class)->put(['stripe_secret_key' => 'sk_test_x']);
        Http::fake(['api.stripe.com/v1/balance' => Http::response(['object' => 'balance'])]);

        $this->actingAs($this->admin())->post(route('admin.settings.test', 'stripe'))
            ->assertSessionHas('status', 'Connexion à Stripe réussie.');
    }

    public function test_blocking_a_sample_removes_it_from_sale(): void
    {
        $sample = $this->sample($this->artist(), ['title' => 'A moderer']);

        $this->actingAs($this->admin())->patch(route('admin.samples.status', $sample))->assertRedirect();
        $this->assertSame(Sample::STATUS_BLOCKED, $sample->fresh()->status);

        auth()->logout();
        $this->get(route('explore'))->assertDontSee(route('samples.show', $sample));
        $this->get(route('samples.show', $sample))->assertNotFound();
    }

    public function test_suspending_an_artist(): void
    {
        $artist = $this->artist();

        $this->actingAs($this->admin())->patch(route('admin.artists.status', $artist))->assertRedirect();
        $this->assertSame(User::STATUS_SUSPENDED, $artist->fresh()->status);

        $this->actingAs($artist->fresh())->get(route('studio.dashboard'))->assertForbidden();
    }

    public function test_invoice_correction_issues_credit_note_and_new_invoice(): void
    {
        $order = Order::query()->create([
            'user_id' => $this->buyer()->id, 'status' => Order::STATUS_PAID, 'provider' => 'paypal',
            'total_cents' => 600, 'paid_at' => now(), 'billing_name' => 'Wrong Name', 'billing_email' => 'c@example.com',
        ]);
        $order->items()->create(['item_type' => 'sample', 'title' => 'Kick', 'price_cents' => 600, 'commission_bp' => 3000, 'artist_earning_cents' => 420]);
        $invoice = app(InvoiceIssuer::class)->forOrder($order->load('items'));

        $this->actingAs($this->admin())->post(route('admin.invoices.correct.store', $invoice), [
            'buyer_name' => 'Right Name',
        ])->assertRedirect();

        $this->assertSame(3, Invoice::query()->count());
        $this->assertTrue($invoice->fresh()->isCancelled());
        $this->assertSame('Right Name', Invoice::query()->invoices()->latest('id')->first()->buyer_name);
        $this->get(route('admin.invoices.export'))->assertOk();
        $this->get(route('admin.invoices.show', $invoice))->assertOk()->assertSee('Annulée');
    }

    public function test_category_management(): void
    {
        $family = Category::query()->where('slug', 'vocals')->firstOrFail();

        $this->actingAs($this->admin())->post(route('admin.categories.store'), ['name' => 'Vocal Loops', 'parent_id' => $family->id])->assertRedirect();
        $category = Category::query()->where('name', 'Vocal Loops')->firstOrFail();
        $this->assertSame('vocals-vocal-loops', $category->slug);

        $this->sample($this->artist(), ['category_id' => $category->id]);
        $this->delete(route('admin.categories.destroy', $category))->assertSessionHas('error');
        $this->assertNotNull($category->fresh());
    }
}
