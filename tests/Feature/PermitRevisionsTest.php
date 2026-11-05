<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Support\PermitEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PermitRevisionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.recaptcha.site_key' => 'k', 'services.recaptcha.secret_key' => 's', 'site.admin_email' => 'admin@example.com']);
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true])]);
        RateLimiter::clear('permit-m:127.0.0.1');
    }

    private function answers(array $o = []): array
    {
        return array_merge(['need' => 'fix_comments', 'ptype' => 'residential', 'scope' => 'addition', 'country' => 'United States', 'sheets' => 12, 'comments' => 18,
            'areas' => ['structural', 'zoning'], 'format' => 'pdf'], $o);
    }

    private function payload(array $o = []): array
    {
        return array_merge($this->answers(), [
            'state' => 'California', 'authority' => 'City of La Verne', 'seal' => 'no', 'name' => 'Pat Client', 'email' => 'pat@example.com',
            'consent' => '1', 'g-recaptcha-response' => 'tok', 'files' => [UploadedFile::fake()->createWithContent('letter.pdf', "%PDF-1.4\n1 0 obj<<>>endobj")],
        ], $o);
    }

    public function test_worked_example_is_840_to_1160(): void
    {
        $e = PermitEstimator::estimate($this->answers());
        $this->assertFalse($e['manual']);
        $this->assertSame(840, $e['price_low']);
        $this->assertSame(1160, $e['price_high']);
        $this->assertSame([52, 73], [$e['hours_low'], $e['hours_high']]);
    }

    public function test_every_manual_quote_trigger(): void
    {
        foreach ([['need' => 'new_set'], ['need' => 'unsure'], ['country' => 'Canada'], ['comments' => 61], ['sheets' => 41], ['scope' => 'change_use'], ['ptype' => 'other']] as $o) {
            $this->assertTrue(PermitEstimator::estimate($this->answers($o))['manual'], json_encode($o));
        }
        $this->assertFalse(PermitEstimator::estimate($this->answers(['comments' => 60, 'sheets' => 40]))['manual']);
    }

    public function test_page_nav_sitemap_robots(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTrailingSlash::class);   // the test client drops the trailing slash
        $this->get('/permit-revisions/')->assertOk()->assertSee('Permit drawing revisions', false)->assertSee('pr-config', false);
        $this->get('/sitemap.xml')->assertSee('/permit-revisions/', false);
        $this->get('/robots.txt')->assertSee('/permit-revisions/send', false);
        $this->get('/cad-drafting-services/')->assertSee('/permit-revisions/', false);
        $this->get('/')->assertSee('Permit Revisions', false);
    }

    public function test_submission_creates_request_files_and_mails(): void
    {
        Mail::fake();
        $this->postJson('/permit-revisions/send', $this->payload(['nda' => '1']))->assertOk()->assertJson(['ok' => true]);

        $order = Order::with('files')->latest('id')->first();
        $this->assertSame('permit-revisions', $order->service);
        $this->assertCount(1, $order->files);
        $this->assertStringContainsString('NDA requested', $order->title);
        $this->assertSame('pat@example.com', $order->customer->email);
        $this->assertStringContainsString('$840', (string) $order->messages()->first()->body);
    }

    public function test_existing_customer_is_reused(): void
    {
        Mail::fake();
        $u = User::create(['role' => 'customer', 'first_name' => 'Pat', 'email' => 'pat@example.com', 'is_active' => true]);
        $this->postJson('/permit-revisions/send', $this->payload())->assertOk();
        $this->assertSame(1, User::where('role', 'customer')->count());
        $this->assertSame($u->id, Order::latest('id')->first()->customer_id);
    }

    public function test_honeypot_stores_nothing(): void
    {
        $this->postJson('/permit-revisions/send', $this->payload(['website' => 'spam.com']))->assertOk();
        $this->assertSame(0, Order::count());
    }

    public function test_validation_and_file_rules(): void
    {
        $this->postJson('/permit-revisions/send', $this->payload(['consent' => null]))->assertStatus(422)->assertJsonValidationErrors('consent');
        $this->postJson('/permit-revisions/send', $this->payload(['files' => []]))->assertStatus(422)->assertJsonValidationErrors('files');
        $this->postJson('/permit-revisions/send', $this->payload(['files' => [UploadedFile::fake()->createWithContent('x.pdf', 'not a pdf')]]))->assertStatus(422);
        $this->postJson('/permit-revisions/send', $this->payload(['files' => [UploadedFile::fake()->createWithContent('x.exe', 'MZ')]]))->assertStatus(422);
        $this->postJson('/permit-revisions/send', $this->payload(['files' => [UploadedFile::fake()->create('big.pdf', 11 * 1024, 'application/pdf')]]))->assertStatus(422);
        $this->assertSame(0, Order::count());
        Mail::fake();
        \Illuminate\Support\Facades\Cache::flush();
        $this->postJson('/permit-revisions/send', $this->payload(['files' => [], 'no_letter' => '1']))->assertOk();   // allowed when the letter is not available yet
    }

    public function test_admin_email_is_refused(): void
    {
        User::create(['role' => 'admin', 'first_name' => 'Boss', 'email' => 'boss@example.com', 'password' => bcrypt('x'), 'is_active' => true]);
        $this->postJson('/permit-revisions/send', $this->payload(['email' => 'boss@example.com']))->assertStatus(422);
    }

    public function test_rate_limit(): void
    {
        $this->postJson('/permit-revisions/send', $this->payload(['consent' => null]));
        for ($i = 0; $i < 5; $i++) {
            $r = $this->postJson('/permit-revisions/send', $this->payload(['consent' => null]));
        }
        $r->assertStatus(429);
    }
}
