<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Cms\TwoFactor;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Two-factor authentication set-up (mandatory at the first login, unless
 * ADMIN_REQUIRE_2FA=false).
 */
class TwoFactorSetupController extends Controller
{
    public function show(Request $request, TwoFactor $twoFactor): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.dashboard');
        }

        $secret = $request->session()->get('admin.2fa_setup_secret');
        if (! $secret) {
            $secret = $twoFactor->generateSecret();
            $request->session()->put('admin.2fa_setup_secret', $secret);
        }

        return view('admin.auth.two-factor-setup', [
            'qr' => $twoFactor->qrCodeSvg($user->email, $secret),
            'secret' => trim(chunk_split($secret, 4, ' ')),
            'required' => config('mycms.require_2fa'),
        ]);
    }

    public function confirm(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $user = $request->user();
        $secret = $request->session()->get('admin.2fa_setup_secret');
        $request->validate(['code' => ['required', 'string', 'max:20']], ['code.required' => __('Type the code shown in the app.')]);

        $timestamp = $secret ? $twoFactor->verify($secret, $request->input('code')) : false;
        if ($timestamp === false) {
            return back()->withErrors(['code' => __('This code does not match. Check the time of your phone and type the current code.')]);
        }

        $codes = $twoFactor->recoveryCodes();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_last_timestamp' => $timestamp,
            'two_factor_recovery_codes' => $codes,
        ])->save();

        $request->session()->forget('admin.2fa_setup_secret');
        ActivityLog::record('2fa.enabled', __('Two-factor authentication enabled'));

        return redirect()->route('admin.account.edit')
            ->with('recovery_codes', $codes)
            ->with('status', __('Two-factor authentication is enabled. Write down your recovery codes below.'));
    }
}
