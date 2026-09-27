<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'phone', 'subject', 'message', 'locale'])]
class ContactMessage extends Model
{
    protected function casts(): array
    {
        // Potentially sensitive data: encrypted in the database with APP_KEY
        return [
            'name' => 'encrypted',
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'subject' => 'encrypted',
            'message' => 'encrypted',
            'read_at' => 'datetime',
        ];
    }
}
