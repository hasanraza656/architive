<?php

namespace Tests\Feature\Portal;

use App\Mail\Portal\LoginCodeMail;
use App\Models\LoginCode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/** Sign-in codes must not be usable to flood strangers' inboxes (bounces were hurting the server's mail reputation). */
class LoginAbuseTest extends PortalTestCase
{
    public function test_captcha_is_required_when_keys_are_configured(): void
    {
        Mail::fake();
        config(['services.recaptcha.site_key' => 'k', 'services.recaptcha.secret_key' => 's']);
        Http::fake(['www.google.com/*' => Http::sequence()->push(['success' => false])->push(['success' => true])]);

        $this->post('/account/login', ['email' => 'stranger@example.test'])->assertSessionHasErrors('g-recaptcha-response');
        $this->post('/account/login', ['email' => 'stranger@example.test', 'g-recaptcha-response' => 'bad'])->assertSessionHasErrors('g-recaptcha-response');
        Mail::assertNothingSent();

        $this->post('/account/login', ['email' => 'stranger@example.test', 'g-recaptcha-response' => 'ok'])->assertRedirect(route('customer.login.verify'));
        Mail::assertSent(LoginCodeMail::class, 1);
    }

    public function test_honeypot_sends_nothing(): void
    {
        Mail::fake();
        $this->post('/account/login', ['email' => 'bot-target@example.test', 'website' => 'http://spam.test'])->assertRedirect(route('customer.login.verify'));
        Mail::assertNothingSent();
        $this->assertSame(0, LoginCode::count());
    }

    public function test_one_address_gets_at_most_three_codes_an_hour(): void
    {
        Mail::fake();
        for ($i = 0; $i < 6; $i++) {
            $this->travel(60)->seconds();                         // past the 45 s resend gap
            $this->post('/account/login', ['email' => 'victim@example.test']);
        }
        Mail::assertSent(LoginCodeMail::class, 3);
    }

    public function test_one_visitor_cannot_request_codes_for_many_addresses(): void
    {
        Mail::fake();
        for ($i = 0; $i < 8; $i++) {
            $this->post('/account/login', ['email' => "target{$i}@example.test"]);
        }
        Mail::assertSent(LoginCodeMail::class, 5);                 // 5 a minute per visitor, the rest are refused
    }
}
