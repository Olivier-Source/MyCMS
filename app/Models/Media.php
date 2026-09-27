<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['path', 'original_name', 'mime', 'size', 'width', 'height', 'alt'])]
class Media extends Model
{
    protected $table = 'media';

    public function url(): string
    {
        // Always on the public domain (even from the administration)
        return site_url('/media/'.$this->path).'?v='.$this->updated_at?->timestamp;
    }

    public function humanSize(): string
    {
        return $this->size >= 1048576
            ? __(':size MB', ['size' => round($this->size / 1048576, 1)])
            : __(':size KB', ['size' => (int) round($this->size / 1024)]);
    }
}
