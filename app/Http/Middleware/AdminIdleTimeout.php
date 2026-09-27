<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Automatic logout after a period of inactivity in the administration.
 */
class AdminIdleTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $limit = config('mycms.idle_minutes') * 60;
        $last = (int) $request->session()->get('admin.last_activity', time());

        if (Auth::check() && time() - $last > $limit) {
            ActivityLog::record('logout.idle', __('Automatic logout after inactivity'));
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->with('status', __('You have been logged out after a period of inactivity.'));
        }

        $request->session()->put('admin.last_activity', time());

        return $next($request);
    }
}
