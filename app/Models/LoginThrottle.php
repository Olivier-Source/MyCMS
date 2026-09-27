<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'failures', 'level', 'locked_until', 'last_failure_at'])]
class LoginThrottle extends Model
{
    protected function casts(): array
    {
        return [
            'locked_until' => 'datetime',
            'last_failure_at' => 'datetime',
        ];
    }
}
