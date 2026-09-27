<?php

namespace App\Console\Commands;

use App\Cms\LoginGuard;
use App\Models\ActivityLog;
use Illuminate\Console\Command;

class UnlockCommand extends Command
{
    protected $signature = 'mycms:unlock {email : E-mail address of the blocked account}';

    protected $description = 'Unblock the logins of an account immediately after failed attempts';

    public function handle(LoginGuard $guard): int
    {
        $email = mb_strtolower(trim($this->argument('email')));
        $guard->clear($email);
        ActivityLog::record('login.unlocked', 'Logins unblocked for '.$email.' (console)', null);
        $this->info('Attempt counter reset for '.$email.'.');

        return self::SUCCESS;
    }
}
