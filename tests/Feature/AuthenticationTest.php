<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountLocked;
use Database\Seeders\StarterContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    private function login(string $email, string $password = 'password')
    {
        return $this->post(route('admin.login.store'), ['email' => $email, 'password' => $password]);
    }

    /** Error message of the e-mail field (errors are stored as an array in the session) */
    private function emailError(): string
    {
        $errors = session('errors');

        return is_array($errors) ? ($errors['default']['messages']['email'][0] ?? '') : (string) $errors?->first('email');
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.pages.index'))->assertRedirect(route('admin.login'));
    }

    public function test_the_public_site_never_links_to_the_administration(): void
    {
        $this->seed(StarterContentSeeder::class);

        $this->get('/')->assertOk()->assertDontSee('/admin', false);
    }

    public function test_non_admin_accounts_cannot_log_in(): void
    {
        $user = User::factory()->create();

        $this->login($user->email)->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_first_login_forces_two_factor_setup(): void
    {
        $admin = User::factory()->admin()->create();

        $this->login($admin->email)->assertRedirect(route('admin.two-factor.setup'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.two-factor.setup'));
        $this->get(route('admin.two-factor.setup'))->assertOk()->assertSee('Scan this code');
    }

    public function test_two_factor_can_be_optional(): void
    {
        config(['mycms.require_2fa' => false]);
        $admin = User::factory()->admin()->create();

        $this->login($admin->email)->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Two-factor authentication is not enabled');
    }

    public function test_two_factor_setup_requires_a_valid_code(): void
    {
        $admin = User::factory()->admin()->create();
        $this->login($admin->email);
        $this->get(route('admin.two-factor.setup'));
        $secret = session('admin.2fa_setup_secret');

        $this->post(route('admin.two-factor.confirm'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertFalse($admin->fresh()->hasTwoFactorEnabled());

        $code = (new Google2FA)->getCurrentOtp($secret);
        $this->post(route('admin.two-factor.confirm'), ['code' => $code])->assertRedirect(route('admin.account.edit'));

        $fresh = $admin->fresh();
        $this->assertTrue($fresh->hasTwoFactorEnabled());
        $this->assertCount(8, $fresh->two_factor_recovery_codes);
        // The secret is encrypted in the database
        $this->assertStringNotContainsString($secret, (string) DB::table('users')->where('id', $admin->id)->value('two_factor_secret'));
    }

    public function test_login_with_two_factor_code(): void
    {
        $admin = User::factory()->admin()->withTwoFactor(self::SECRET)->create();

        $this->login($admin->email)->assertRedirect(route('admin.two-factor.challenge'));
        $this->assertGuest();

        $code = (new Google2FA)->getCurrentOtp(self::SECRET);
        $this->post(route('admin.two-factor.verify'), ['code' => $code])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Hello');
    }

    public function test_the_administration_follows_the_language_of_the_administrator(): void
    {
        $admin = User::factory()->admin()->withTwoFactor(self::SECRET)->create(['locale' => 'fr']);

        $this->actingAs($admin)->withSession(['admin.last_activity' => time()])
            ->get(route('admin.dashboard'))->assertOk()->assertSee('Tableau de bord')->assertSee('Bonjour');
    }

    public function test_a_two_factor_code_cannot_be_reused(): void
    {
        $admin = User::factory()->admin()->withTwoFactor(self::SECRET)->create();
        $code = (new Google2FA)->getCurrentOtp(self::SECRET);

        $this->login($admin->email);
        $this->post(route('admin.two-factor.verify'), ['code' => $code]);
        $this->post(route('admin.logout'));

        $this->login($admin->email);
        $this->post(route('admin.two-factor.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_codes_work_only_once(): void
    {
        $admin = User::factory()->admin()->withTwoFactor(self::SECRET, ['AAAAABBBBB'])->create();

        $this->login($admin->email);
        $this->post(route('admin.two-factor.verify'), ['recovery_code' => 'aaaaa-bbbbb'])->assertRedirect(route('admin.account.edit'));
        $this->assertAuthenticatedAs($admin);
        $this->post(route('admin.logout'));

        $this->login($admin->email);
        $this->post(route('admin.two-factor.verify'), ['recovery_code' => 'AAAAA-BBBBB'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_three_failures_lock_the_account_for_five_minutes(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->withTwoFactor(self::SECRET)->create();

        $this->login($admin->email, 'wrong-1')->assertSessionHasErrors('email');
        $this->login($admin->email, 'wrong-2')->assertSessionHasErrors('email');
        $this->login($admin->email, 'wrong-3');
        $this->assertStringContainsString('5 minutes', $this->emailError());

        // Even the right password is refused during the lock
        $this->login($admin->email)->assertSessionHasErrors('email');
        $this->assertGuest();
        Notification::assertSentTo($admin, AccountLocked::class);

        // After 5 minutes, logging in is possible again
        $this->travel(6)->minutes();
        $this->login($admin->email)->assertRedirect(route('admin.two-factor.challenge'));
    }

    public function test_lockout_duration_increases_after_each_new_failure(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->withTwoFactor(self::SECRET)->create();

        foreach (range(1, 3) as $i) {
            $this->login($admin->email, 'wrong');
        }
        $this->travel(6)->minutes();

        // A single new failure then locks again, for 10 minutes
        $this->login($admin->email, 'wrong');
        $this->assertStringContainsString('10 minutes', $this->emailError());

        $this->travel(11)->minutes();
        $this->login($admin->email, 'wrong');
        $this->assertStringContainsString('20 minutes', $this->emailError());
    }

    public function test_unknown_emails_get_the_same_lockout_as_real_accounts(): void
    {
        foreach (range(1, 3) as $i) {
            $this->login('nobody@example.com', 'wrong');
        }

        $this->assertStringContainsString('5 minutes', $this->emailError());
    }

    public function test_wrong_two_factor_codes_count_towards_the_lockout(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->withTwoFactor(self::SECRET)->create();

        foreach (range(1, 3) as $i) {
            $this->login($admin->email);
            $this->post(route('admin.two-factor.verify'), ['code' => '000000']);
        }

        $this->login($admin->email)->assertSessionHasErrors('email');
        $this->assertStringContainsString('blocked', $this->emailError());
    }

    public function test_the_unlock_command_clears_the_lock(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->withTwoFactor(self::SECRET)->create();
        foreach (range(1, 3) as $i) {
            $this->login($admin->email, 'wrong');
        }

        $this->artisan('mycms:unlock', ['email' => $admin->email])->assertSuccessful();
        $this->login($admin->email)->assertRedirect(route('admin.two-factor.challenge'));
    }

    public function test_admin_command_creates_an_administrator(): void
    {
        $this->artisan('mycms:admin', ['email' => 'jane@example.com', '--name' => 'Jane'])
            ->expectsQuestion('Password (12 characters min., upper and lower case letters and digits)', 'A-Good-Password-2026')
            ->expectsQuestion('Confirm the password', 'A-Good-Password-2026')
            ->assertSuccessful();

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertTrue($user->is_admin);
        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function test_admin_command_promotes_and_revokes(): void
    {
        $user = User::factory()->create(['email' => 'editor@example.com']);

        $this->artisan('mycms:admin', ['email' => 'editor@example.com'])
            ->expectsConfirmation("Give administration rights to {$user->name} (editor@example.com)?", 'yes')
            ->assertSuccessful();
        $this->assertTrue($user->fresh()->is_admin);

        $this->artisan('mycms:admin', ['email' => 'editor@example.com', '--revoke' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->is_admin);
    }
}
