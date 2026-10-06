<?php

namespace Tests\Feature;

use App\Mail\ContactEnquiry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    private function payload(array $over = []): array
    {
        return array_merge([
            'name'     => 'Test User',
            'email'    => 'visitor@example.com',
            'company'  => 'Test Studio',
            'audience' => 'firm',
            'service'  => 'cad',
            'message'  => 'Please quote twelve sheets of redline updates.',
            'consent'  => '1',
            'nda'      => '1',
            'g-recaptcha-response' => 'token-from-widget',
        ], $over);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.recaptcha.site_key'   => 'site-key',
            'services.recaptcha.secret_key' => 'secret-key',
            'site.admin_email'              => 'admin@example.com',
        ]);
    }

    public function test_valid_enquiry_with_passing_captcha_emails_the_admin(): void
    {
        Mail::fake();
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true])]);

        $this->postJson('/contact/send', $this->payload())->assertOk()->assertJson(['ok' => true]);

        Mail::assertSent(ContactEnquiry::class, function (ContactEnquiry $m) {
            return $m->hasTo('admin@example.com')
                && $m->hasReplyTo('visitor@example.com')
                && str_contains($m->envelope()->subject, 'CAD drafting')
                && $m->enquiry['name'] === 'Test User'
                && ! array_key_exists('g-recaptcha-response', $m->enquiry);
        });
        Http::assertSent(fn ($r) => $r['secret'] === 'secret-key' && $r['response'] === 'token-from-widget');
    }

    public function test_missing_captcha_is_rejected_and_nothing_is_sent(): void
    {
        Mail::fake();
        Http::fake();

        $this->postJson('/contact/send', $this->payload(['g-recaptcha-response' => '']))
            ->assertStatus(422)->assertJsonValidationErrors('g-recaptcha-response');

        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_failed_captcha_is_rejected_and_nothing_is_sent(): void
    {
        Mail::fake();
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);

        $this->postJson('/contact/send', $this->payload())->assertStatus(422)->assertJsonValidationErrors('g-recaptcha-response');

        Mail::assertNothingSent();
    }

    public function test_google_outage_rejects_instead_of_letting_spam_through(): void
    {
        Mail::fake();
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response('error', 500)]);

        $this->postJson('/contact/send', $this->payload())->assertStatus(422);

        Mail::assertNothingSent();
    }

    public function test_honeypot_submissions_look_successful_but_send_nothing(): void
    {
        Mail::fake();
        Http::fake();

        $this->postJson('/contact/send', $this->payload(['website' => 'http://spam.example']))->assertOk()->assertJson(['ok' => true]);

        Mail::assertNothingSent();
    }

    public function test_other_field_validation_still_applies(): void
    {
        Mail::fake();
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true])]);

        $this->postJson('/contact/send', $this->payload(['email' => 'not-an-email', 'consent' => null, 'message' => 'short']))
            ->assertStatus(422)->assertJsonValidationErrors(['email', 'consent', 'message']);

        Mail::assertNothingSent();
    }

    public function test_mail_failure_returns_a_friendly_error(): void
    {
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true])]);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->postJson('/contact/send', $this->payload())->assertStatus(500)->assertJson(['ok' => false]);
    }

    public function test_missing_secret_key_fails_closed_outside_local_and_testing(): void
    {
        config(['services.recaptcha.secret_key' => '']);
        $this->app['env'] = 'production';   // CSRF is enforced outside the testing env, so skip it here
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        Mail::fake();

        $this->postJson('/contact/send', $this->payload())->assertStatus(422);

        Mail::assertNothingSent();
    }

    public function test_contact_page_renders_the_captcha_widget_when_configured(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTrailingSlash::class)   // the test client drops the trailing slash
            ->get('/contact/')->assertOk()->assertSee('data-captcha', false)->assertSee('data-sitekey="site-key"', false)->assertDontSee('recaptcha/api.js', false);   // the script is loaded lazily by interactive.js
    }

    public function test_selected_situation_is_included_in_the_admin_email(): void
    {
        Mail::fake();
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true])]);

        $this->postJson('/contact/send', $this->payload(['topic' => 'I have sketches, PDFs or markups']))->assertOk();

        Mail::assertSent(ContactEnquiry::class, fn (ContactEnquiry $m) => $m->enquiry['topic'] === 'I have sketches, PDFs or markups');
    }

    public function test_overlong_topic_is_rejected(): void
    {
        Mail::fake();
        Http::fake();

        $this->postJson('/contact/send', $this->payload(['topic' => str_repeat('x', 161)]))->assertStatus(422)->assertJsonValidationErrors('topic');
        Mail::assertNothingSent();
    }

    public function test_home_page_has_the_enquiry_popup_but_the_contact_page_does_not(): void
    {
        $this->get('/')->assertOk()->assertSee('id="enquiryModal"', false)->assertDontSee('1,500+')->assertSee('1,200+')->assertSee('From $16 / hour');
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTrailingSlash::class)->get('/contact/')->assertOk()->assertDontSee('id="enquiryModal"', false);
    }

    public function test_team_page_is_parked_out_of_search(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTrailingSlash::class)->get('/team/')->assertOk()->assertSee('noindex', false);
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('/team/', false);
    }
}
