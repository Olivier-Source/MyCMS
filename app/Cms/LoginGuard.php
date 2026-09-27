<?php

namespace App\Cms;

use App\Models\LoginThrottle;
use Illuminate\Support\Carbon;

/**
 * Progressive lockout of the administration logins.
 *
 * - 3 failures → locked for 5 minutes;
 * - after a lock, each new failure locks again, doubling the duration
 *   (10 min, 20 min, 40 min… up to 24 h);
 * - a successful login, or 24 h without failure, resets the counter.
 *
 * Tracking uses the hashed e-mail address, whether the account exists or not:
 * an attacker cannot find out whether an address matches an account.
 */
class LoginGuard
{
    public static function key(string $email): string
    {
        return hash('sha256', 'admin|'.mb_strtolower(trim($email)));
    }

    /** Remaining lock seconds (0 when free). */
    public function lockedFor(string $email): int
    {
        $row = LoginThrottle::where('key', self::key($email))->first();

        if (! $row?->locked_until || $row->locked_until->isPast()) {
            return 0;
        }

        return (int) ceil(now()->diffInSeconds($row->locked_until, true));
    }

    /** Records a failure; returns the end of the lock, if any. */
    public function recordFailure(string $email): ?Carbon
    {
        $cfg = config('mycms.lockout');
        $row = LoginThrottle::firstOrNew(['key' => self::key($email)]);

        if ($row->last_failure_at && $row->last_failure_at->lt(now()->subHours($cfg['reset_after_hours']))) {
            $row->failures = 0;
            $row->level = 0;
        }

        $row->failures = ($row->failures ?? 0) + 1;
        $row->last_failure_at = now();

        if (($row->level ?? 0) === 0 && $row->failures >= $cfg['max_attempts']) {
            $row->level = 1;
        } elseif (($row->level ?? 0) >= 1) {
            $row->level++;
        }

        if ($row->level >= 1) {
            $minutes = min($cfg['base_minutes'] * (2 ** ($row->level - 1)), $cfg['max_minutes']);
            $row->locked_until = now()->addMinutes($minutes);
        }

        $row->save();

        return $row->level >= 1 ? $row->locked_until : null;
    }

    public function clear(string $email): void
    {
        LoginThrottle::where('key', self::key($email))->delete();
    }

    public static function humanDuration(int $seconds): string
    {
        $minutes = (int) ceil($seconds / 60);
        if ($minutes < 60) {
            return trans_choice(':count minute|:count minutes', $minutes);
        }
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $hours.' h'.($rest ? ' '.str_pad((string) $rest, 2, '0', STR_PAD_LEFT) : '');
    }
}
