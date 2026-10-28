<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WebsiteTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        return ['request_token' => str_repeat('a', 40), 'name' => 'Test Client', 'email' => 'client@example.test', 'phone' => '+260973144975', 'company' => 'Example Business', 'service' => 'transport', 'message' => 'Fictional freight requirement for testing.', 'origin' => 'Lusaka', 'destination' => 'Dar es Salaam', 'consent' => '1'];
    }

    private function token(): void
    {
        $this->withSession(['request_token' => str_repeat('a', 40)]);
    }

    private function admin(bool $forced = false): User
    {
        $u = User::create(['name' => 'Team', 'email' => 'admin@example.test', 'password' => 'StrongPassword2026!', 'must_change_password' => $forced]);
        $this->actingAs($u)->withSession(['account_version' => 1]);

        return $u;
    }

    private function enquiry(array $extra = []): Enquiry
    {
        $d = $this->data();
        unset($d['request_token'],$d['consent']);

        return Enquiry::create(array_merge($d, ['type' => 'quote', 'reference' => 'MTC-'.uniqid(), 'request_key' => uniqid(), 'status' => 'new'], $extra));
    }

    public function test_public_pages_and_company_profile_are_available(): void
    {
        foreach (['/', '/about', '/services', '/contact', '/privacy', '/services/transport', '/services/supply-chain', '/services/real-estate', '/services/investments', '/services/consultancy', '/request/quote', '/request/consultation'] as $p) {
            $this->get($p)->assertOk();
        }$this->get('/services/unknown')->assertNotFound();
        $this->assertFileExists(public_path('Musonza-Company-Profile.pdf'));
    }

    public function test_quote_persists_encrypted_information_and_duplicate_submission_is_idempotent(): void
    {
        $this->token();
        $this->post('/request/quote', $this->data())->assertRedirect('/thank-you');
        $e = Enquiry::first();
        $this->assertSame('Test Client', $e->name);
        $this->assertNotSame('Test Client', DB::table('enquiries')->value('name'));
        $this->assertNotSame('client@example.test', DB::table('enquiries')->value('email'));
        $this->post('/request/quote', $this->data())->assertRedirect('/thank-you');
        $this->assertDatabaseCount('enquiries', 1);
        $this->assertDatabaseCount('enquiry_events', 1);
    }

    public function test_forms_validate_consent_services_and_honeypot(): void
    {
        $this->token();
        $d = $this->data();
        unset($d['consent']);
        $this->post('/request/quote', $d)->assertSessionHasErrors('consent');
        $d = $this->data();
        $d['service'] = 'unknown';
        $this->post('/request/quote', $d)->assertSessionHasErrors('service');
        $this->post('/request/quote', $this->data() + ['website' => 'spam'])->assertSessionHasErrors('form');
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_request_token_is_bound_to_current_session(): void
    {
        $this->token();
        $d = $this->data();
        $d['request_token'] = str_repeat('b', 40);
        $this->post('/request/quote', $d)->assertStatus(419);
    }

    public function test_consultation_only_accepts_future_weekday_half_hour_slots(): void
    {
        $this->travelTo(Carbon::parse('2026-10-27 10:00', 'Africa/Lusaka'));
        $this->token();
        $d = $this->data() + ['preferred_at' => '2026-10-28T10:30'];
        $this->post('/request/consultation', $d)->assertRedirect('/thank-you');
        $this->assertSame('10:30', Enquiry::first()->preferred_at->format('H:i'));
        $this->token();
        $this->withSession(['request_token' => str_repeat('b', 40)]);
        $d['request_token'] = str_repeat('b', 40);
        $d['preferred_at'] = '2026-10-31T10:30';
        $this->post('/request/consultation', $d)->assertSessionHasErrors('preferred_at');
        $d['preferred_at'] = '2026-10-28T10:15';
        $this->post('/request/consultation', $d)->assertSessionHasErrors('preferred_at');
        $this->travelBack();
    }

    public function test_admin_records_and_settings_are_private(): void
    {
        foreach (['/admin', '/admin/settings', '/admin/enquiries/1'] as $p) {
            $this->get($p)->assertRedirect('/admin/login');
        }$this->assertGuest();
    }

    public function test_login_password_gate_and_session_revocation(): void
    {
        $u = User::create(['name' => 'Team', 'email' => 'admin@example.test', 'password' => 'StrongPassword2026!']);
        $this->post('/admin/login', ['email' => $u->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/admin/login', ['email' => $u->email, 'password' => 'StrongPassword2026!'])->assertRedirect('/admin');
        $this->get('/admin')->assertRedirect('/admin/password');
        $this->patch('/admin/password', ['current_password' => 'StrongPassword2026!', 'password' => 'ChangedPassword2026!', 'password_confirmation' => 'ChangedPassword2026!'])->assertRedirect('/admin');
        $this->get('/admin')->assertOk();
        $u->refresh()->increment('session_version');
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_suspended_account_cannot_sign_in(): void
    {
        $u = User::create(['name' => 'Team', 'email' => 'admin@example.test', 'password' => 'StrongPassword2026!', 'active' => false]);
        $this->post('/admin/login', ['email' => $u->email, 'password' => 'StrongPassword2026!'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_updates_are_audited_and_render_text_safely(): void
    {
        $this->admin();
        $e = $this->enquiry(['message' => '<script>alert(1)</script>']);
        $this->get('/admin/enquiries/'.$e->id)->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->patch('/admin/enquiries/'.$e->id, ['status' => 'reviewing', 'internal_note' => 'Called the client'])->assertRedirect();
        $this->assertSame('reviewing', $e->fresh()->status);
        $this->assertDatabaseCount('enquiry_events', 1);
        $this->assertNotSame('Called the client', DB::table('enquiry_events')->value('note'));
    }

    public function test_confirmed_consultation_slot_cannot_be_double_booked(): void
    {
        $this->admin();
        $slot = now()->addWeek()->startOfWeek()->addDays(2)->setTime(10, 30);
        $x = $this->enquiry(['type' => 'consultation', 'preferred_at' => $slot]);
        $y = $this->enquiry(['type' => 'consultation', 'preferred_at' => $slot]);
        $this->patch('/admin/enquiries/'.$x->id, ['status' => 'confirmed', 'internal_note' => 'Client contacted'])->assertRedirect();
        $this->patch('/admin/enquiries/'.$y->id, ['status' => 'confirmed', 'internal_note' => 'Client contacted'])->assertStatus(409);
        $this->assertSame('new', $y->fresh()->status);
        $this->patch('/admin/enquiries/'.$x->id, ['status' => 'closed', 'internal_note' => 'Meeting cancelled'])->assertRedirect();
        $this->patch('/admin/enquiries/'.$y->id, ['status' => 'confirmed', 'internal_note' => 'Client contacted'])->assertRedirect();
    }

    public function test_company_settings_update_public_content_and_validate_email(): void
    {
        $this->admin();
        $d = ['phone' => '+260973144975', 'email' => 'info@musonzatransport.com', 'office' => 'Lusaka / Chilanga, Zambia', 'hero_heading' => 'Moving business forward.', 'hero_intro' => 'A tailored introduction from the team.'];
        $this->patch('/admin/settings', $d)->assertRedirect();
        $this->get('/')->assertSee($d['hero_intro']);
        $d['email'] = 'invalid';
        $this->patch('/admin/settings', $d)->assertSessionHasErrors('email');
    }

    public function test_security_headers_and_search_metadata(): void
    {
        $this->get('/')->assertHeader('X-Frame-Options', 'DENY')->assertHeader('X-Content-Type-Options', 'nosniff')->assertSee('canonical');
        $this->get('/robots.txt')->assertSee('Disallow: /admin');
        $this->get('/sitemap.xml')->assertOk()->assertSee('/services/transport');
        $this->get('/admin/login')->assertHeader('Cache-Control', 'no-store, private');
    }
}
