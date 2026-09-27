<?php

namespace App\Http\Controllers\Admin;

use App\Cms\Languages\LanguageManager;
use App\Cms\TwoFactor;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function edit(Request $request, LanguageManager $languages): View
    {
        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity']);

        return view('admin.account.edit', [
            'user' => $request->user(),
            'sessions' => $sessions,
            'currentSession' => $request->session()->getId(),
            'languages' => $languages->options(),
            'require2fa' => config('mycms.require_2fa'),
        ]);
    }

    public function updateProfile(Request $request, LanguageManager $languages): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users')->ignore($user->id)],
            'locale' => ['nullable', Rule::in(array_keys($languages->all()))],
            'current_password' => ['required', 'current_password'],
        ], ['current_password.current_password' => __('Incorrect current password.')], ['name' => __('name'), 'current_password' => __('current password')]);

        $user->update([
            'name' => strip_tags($data['name']),
            'email' => mb_strtolower($data['email']),
            'locale' => $data['locale'] ?: null,
        ]);
        app()->setLocale($languages->adminLocale($user));
        ActivityLog::record('account.profile', __('Profile updated'));

        return back()->with('status', __('Profile saved.'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ], [
            'current_password.current_password' => __('Incorrect current password.'),
            'password.different' => __('The new password must be different from the current one.'),
        ], ['password' => __('new password'), 'current_password' => __('current password')]);

        $request->user()->forceFill(['password' => $data['password'], 'password_changed_at' => now()])->save();
        // The other signed-in devices are logged out
        Auth::logoutOtherDevices($data['password']);
        $this->deleteOtherSessions($request);
        ActivityLog::record('account.password', __('Password changed'));

        return back()->with('status', __('Password changed. Your other devices have been logged out.'));
    }

    public function regenerateCodes(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']], ['current_password.current_password' => __('Incorrect current password.')]);
        abort_unless($request->user()->hasTwoFactorEnabled(), 404);

        $codes = $twoFactor->recoveryCodes();
        $request->user()->forceFill(['two_factor_recovery_codes' => $codes])->save();
        ActivityLog::record('2fa.codes', __('New recovery codes generated'));

        return back()->with('recovery_codes', $codes)->with('status', __('New recovery codes generated: the old ones no longer work.'));
    }

    /** Only possible when two-factor authentication is not required. */
    public function disableTwoFactor(Request $request): RedirectResponse
    {
        abort_if(config('mycms.require_2fa'), 403);
        $request->validate(['current_password' => ['required', 'current_password']], ['current_password.current_password' => __('Incorrect current password.')]);

        $request->user()->forceFill([
            'two_factor_secret' => null, 'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null, 'two_factor_last_timestamp' => null,
        ])->save();
        ActivityLog::record('2fa.disabled', __('Two-factor authentication disabled'));

        return back()->with('status', __('Two-factor authentication is disabled.'));
    }

    public function logoutOthers(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']], ['current_password.current_password' => __('Incorrect current password.')]);

        Auth::logoutOtherDevices($request->input('current_password'));
        $this->deleteOtherSessions($request);
        ActivityLog::record('account.sessions', __('Other devices logged out'));

        return back()->with('status', __('All your other devices have been logged out.'));
    }

    private function deleteOtherSessions(Request $request): void
    {
        DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }
}
