<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\ContactMessage;
use App\Models\LoginThrottle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * GDPR: automatic deletion of old messages and activity log entries.
 * Scheduled every night (bootstrap/app.php).
 */
class PurgeCommand extends Command
{
    protected $signature = 'mycms:purge';

    protected $description = 'Delete contact messages and activity log entries that are too old';

    public function handle(): int
    {
        $months = config('mycms.messages_retention_months');
        $messages = ContactMessage::where('created_at', '<', now()->subMonths($months))->delete();
        $logs = ActivityLog::where('created_at', '<', now()->subYear())->delete();
        LoginThrottle::where('updated_at', '<', now()->subDays(7))->delete();

        // Leftovers of interrupted package installations
        foreach (glob(storage_path('app/tmp/packages/*')) ?: [] as $path) {
            if (filemtime($path) < time() - 86400) {
                is_dir($path) ? File::deleteDirectory($path) : @unlink($path);
            }
        }

        $this->info("{$messages} message(s) and {$logs} log entry(ies) deleted.");

        return self::SUCCESS;
    }
}
