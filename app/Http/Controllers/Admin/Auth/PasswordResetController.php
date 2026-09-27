<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Cms\LoginGuard;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Forgotten password. Two-factor authentication is still required afterwards:
 * access to the mailbox is not enough to enter the administration.
 */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:190']]);
        $email = mb_strtolower(trim($request->input('email')));

        if (User::where('email', $email)->where('is_admin', true)->exists()) {
            try {
                Password::sendResetLink(['email' => $email]);
            } catch (\Throwable $e) {
                Log::error('Reset e-mail not sent: '.$e->getMessage());
            }
        }

        // Same answer whether the account exists or not
        return back()->with('status', __('If this address matches an account, an e-mail with a reset link has just been sent. The link is valid for 60 minutes.'));
    }

    public function edit(Request $request, string $token): View
    {
        return view('admin.auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request, LoginGuard $guard): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($guard) {
                $user->forceFill([
                    'password' => $password,
                    'password_changed_at' => now(),
                    'remember_token' => Str::random(60),
                ])->save();
                $guard->clear($user->email);
                ActivityLog::record('password.reset', __('Password reset by e-mail'), $user->id);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('admin.login')->with('status', __('Your password has been changed. You can log in.'))
            : back()->withErrors(['email' => __('This link is no longer valid. Ask for a new one.')]);
    }
}
