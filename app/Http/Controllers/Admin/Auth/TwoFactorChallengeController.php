<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Cms\LoginGuard;
use App\Cms\TwoFactor;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Second step of the login: 6-digit code from the app, or recovery code.
 */
class TwoFactorChallengeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        return $this->pending($request) ? view('admin.auth.two-factor') : redirect()->route('admin.login');
    }

    public function store(Request $request, TwoFactor $twoFactor, LoginGuard $guard): RedirectResponse
    {
        $pending = $this->pending($request);
        if (! $pending) {
            return redirect()->route('admin.login')->withErrors(['email' => __('The verification has expired, please log in again.')]);
        }

        $request->validate([
            'code' => ['nullable', 'string', 'max:20'],
            'recovery_code' => ['nullable', 'string', 'max:30'],
        ]);

        if ($seconds = $guard->lockedFor($pending['email'])) {
            $request->session()->forget('admin.2fa');

            return redirect()->route('admin.login')->withErrors(['email' => __('Too many failed attempts. For security reasons, login is blocked for :duration.', ['duration' => LoginGuard::humanDuration($seconds)])]);
        }

        $user = User::find($pending['id']);
        if (! $user?->is_admin || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget('admin.2fa');

            return redirect()->route('admin.login');
        }

        $usedRecovery = false;
        if ($request->filled('recovery_code')) {
            $ok = $user->useRecoveryCode($request->input('recovery_code'));
            $usedRecovery = $ok;
        } else {
            $ok = $twoFactor->verifyUser($user, (string) $request->input('code'));
        }

        if (! $ok) {
            return LoginController::failed($request, $guard, $pending['email'], $user, 'code');
        }

        LoginController::completeLogin($request, $user, $guard);

        if ($usedRecovery) {
            $left = count($user->fresh()->two_factor_recovery_codes ?? []);
            ActivityLog::record('2fa.recovery', __('Login with a recovery code (:count left)', ['count' => $left]));

            return redirect()->route('admin.account.edit')->with('warning', __('You logged in with a recovery code. You have :count left. Remember to generate new ones if needed.', ['count' => $left]));
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    private function pending(Request $request): ?array
    {
        $pending = $request->session()->get('admin.2fa');
        if (! $pending || ($pending['expires'] ?? 0) < time()) {
            $request->session()->forget('admin.2fa');

            return null;
        }

        return $pending;
    }
}
