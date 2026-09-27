<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Cms\Languages\LanguageManager;
use App\Cms\LoginGuard;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\AccountLocked;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    /** Bcrypt hash of an unknown random password (accounts that do not exist). */
    private const DUMMY_HASH = '$2y$12$FRqRAJ6eyn0VBYJA4YgZ3.wHc/F09eSKqvcVps3knyJXhVA6eCAh6';

    public function show(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request, LoginGuard $guard): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $email = mb_strtolower(trim($request->input('email')));

        if ($seconds = $guard->lockedFor($email)) {
            return $this->lockedResponse($seconds);
        }

        $user = User::where('email', $email)->first();

        // Same computing time whether the account exists or not (no enumeration)
        $passwordOk = Hash::check($request->input('password'), $user?->password ?? self::DUMMY_HASH);

        if (! $user || ! $user->is_admin || ! $passwordOk) {
            return $this->failed($request, $guard, $email, $user);
        }

        $request->session()->regenerate();

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('admin.2fa', [
                'id' => $user->id,
                'email' => $email,
                'expires' => now()->addMinutes(5)->timestamp,
            ]);

            return redirect()->route('admin.two-factor.challenge');
        }

        self::completeLogin($request, $user, $guard);

        // First login: two-factor authentication must be set up right away (if required)
        return config('mycms.require_2fa')
            ? redirect()->route('admin.two-factor.setup')
            : redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        ActivityLog::record('logout', __('Logout'));
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', __('You are logged out. See you soon!'));
    }

    public static function completeLogin(Request $request, User $user, LoginGuard $guard): void
    {
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('admin.2fa');
        $request->session()->put('admin.last_activity', time());
        $guard->clear($user->email);

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        app()->setLocale(app(LanguageManager::class)->adminLocale($user));
        ActivityLog::record('login', __('Successful login'), $user->id);
    }

    /**
     * Records the failure, locks if needed and warns the account owner.
     */
    public static function failed(Request $request, LoginGuard $guard, string $email, ?User $user, string $field = 'email'): RedirectResponse
    {
        $until = $guard->recordFailure($email);
        ActivityLog::record('login.failed', __('Failed login (:email)', ['email' => self::mask($email)]), $user?->is_admin ? $user->id : null);

        if ($until) {
            $seconds = (int) ceil(now()->diffInSeconds($until, true));
            ActivityLog::record('login.locked', __('Logins blocked for :duration (:email)', ['duration' => LoginGuard::humanDuration($seconds), 'email' => self::mask($email)]), $user?->is_admin ? $user->id : null);

            if ($user?->is_admin) {
                try {
                    $user->notify(new AccountLocked(LoginGuard::humanDuration($seconds), $request->ip()));
                } catch (\Throwable $e) {
                    Log::warning('Lock alert not sent: '.$e->getMessage());
                }
            }
            $request->session()->forget('admin.2fa');

            return (new self)->lockedResponse($seconds);
        }

        $message = $field === 'code' ? __('This code is not valid.') : __('Incorrect e-mail address or password.');

        return back()->withInput($request->only('email'))->withErrors([$field => $message]);
    }

    private function lockedResponse(int $seconds): RedirectResponse
    {
        return redirect()->route('admin.login')->withErrors([
            'email' => __('Too many failed attempts. For security reasons, login is blocked for :duration.', ['duration' => LoginGuard::humanDuration($seconds)]),
        ]);
    }

    private static function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 2).'•••@'.$domain;
    }
}
