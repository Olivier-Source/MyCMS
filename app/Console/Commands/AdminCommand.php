<?php

namespace App\Console\Commands;

use App\Cms\LoginGuard;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Administrator accounts — from the server only.
 * There is no sign-up form: only someone with access to the server can
 * create or promote an administrator.
 *
 * Non-interactive use (scripts): the password is read from the
 * MYCMS_ADMIN_PASSWORD environment variable.
 */
class AdminCommand extends Command
{
    protected $signature = 'mycms:admin
        {email? : E-mail address of the account}
        {--name= : Display name}
        {--revoke : Remove the administration rights}
        {--reset-2fa : Reset the two-factor authentication (lost phone)}
        {--password : Set a new password}
        {--list : List the administrators}';

    protected $description = 'Create an administrator, promote an existing account, or manage its security';

    public function handle(LoginGuard $guard): int
    {
        if ($this->option('list')) {
            $this->table(['Name', 'E-mail', '2FA', 'Last login'], User::where('is_admin', true)->get()->map(fn (User $u) => [
                $u->name, $u->email, $u->hasTwoFactorEnabled() ? 'enabled' : 'not set up', $u->last_login_at?->format('Y-m-d H:i') ?? '—',
            ]));

            return self::SUCCESS;
        }

        $email = mb_strtolower(trim($this->argument('email') ?? text('E-mail address of the account', required: true, validate: fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Invalid e-mail address.')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid e-mail address.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($this->option('revoke')) {
            if (! $user?->is_admin) {
                $this->warn('This account is not an administrator.');

                return self::SUCCESS;
            }
            $user->forceFill(['is_admin' => false])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            ActivityLog::record('admin.revoked', 'Administration rights removed from '.$email.' (console)', null);
            $this->info('Rights removed, sessions closed.');

            return self::SUCCESS;
        }

        if ($this->option('reset-2fa')) {
            if (! $user) {
                $this->error('No account with this address.');

                return self::FAILURE;
            }
            $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null, 'two_factor_last_timestamp' => null])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            ActivityLog::record('2fa.reset', 'Two-factor authentication reset (console)', $user->id);
            $this->info('Two-factor authentication reset: it will have to be set up at the next login.');

            return self::SUCCESS;
        }

        if ($user && ! $this->option('password')) {
            if ($user->is_admin) {
                $this->info('This account is already an administrator. Options: --password, --reset-2fa, --revoke.');

                return self::SUCCESS;
            }
            if ($this->input->isInteractive() && ! confirm("Give administration rights to {$user->name} ({$email})?")) {
                return self::FAILURE;
            }
            $user->forceFill(['is_admin' => true])->save();
            ActivityLog::record('admin.promoted', 'Account promoted to administrator: '.$email.' (console)', null);
            $this->info('Account promoted to administrator.');

            return self::SUCCESS;
        }

        $plain = $this->askPassword();
        if ($plain === null) {
            return self::FAILURE;
        }

        if ($user) {
            $user->forceFill(['password' => $plain, 'password_changed_at' => now(), 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $guard->clear($email);
            ActivityLog::record('password.console', 'Password set (console)', $user->id);
            $this->info('Password changed and sessions closed.');

            return self::SUCCESS;
        }

        $name = $this->option('name') ?: ($this->input->isInteractive() ? text('Display name', default: Str::before($email, '@'), required: true) : Str::before($email, '@'));
        $user = new User(['name' => strip_tags($name), 'email' => $email, 'password' => $plain]);
        $user->forceFill(['is_admin' => true, 'email_verified_at' => now(), 'password_changed_at' => now()])->save();
        ActivityLog::record('admin.created', 'Administrator created: '.$email.' (console)', null);

        $this->newLine();
        $this->info('Administrator created.');
        if (config('mycms.require_2fa')) {
            $this->line('At the first login, two-factor authentication must be set up');
            $this->line('with an app (Google Authenticator, Microsoft Authenticator, 2FAS…).');
        }
        $this->line('Login address: '.route('admin.login'));

        return self::SUCCESS;
    }

    private function askPassword(): ?string
    {
        $fromEnv = getenv('MYCMS_ADMIN_PASSWORD') ?: null;

        while (true) {
            if ($fromEnv !== null) {
                [$plain, $confirm] = [$fromEnv, $fromEnv];
            } elseif ($this->input->isInteractive()) {
                $plain = password('Password (12 characters min., upper and lower case letters and digits)', required: true);
                $confirm = password('Confirm the password', required: true);
            } else {
                $this->error('No password: run the command interactively or set MYCMS_ADMIN_PASSWORD.');

                return null;
            }

            $validator = Validator::make(
                ['password' => $plain, 'password_confirmation' => $confirm],
                ['password' => ['confirmed', Password::defaults()]],
            );

            if ($validator->passes()) {
                return $plain;
            }
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            if ($fromEnv !== null) {
                return null;
            }
        }
    }
}
