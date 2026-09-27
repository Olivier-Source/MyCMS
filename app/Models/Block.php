<?php

namespace App\Models;

use App\Cms\BlockRegistry;
use App\Cms\Placeholders;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['page_id', 'type', 'position', 'is_visible', 'data'])]
class Block extends Model
{
    protected $touches = ['page'];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'data' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function definition(): ?array
    {
        return BlockRegistry::get($this->type);
    }

    /**
     * Short label shown in the list of sections of the administration.
     */
    public function summary(): string
    {
        $data = $this->data ?? [];
        foreach (['title', 'eyebrow', 'quote', 'badge', 'text'] as $key) {
            if (! empty($data[$key]) && is_string($data[$key])) {
                $text = app(Placeholders::class)->raw(strip_tags($data[$key]));

                return str($text)->squish()->limit(80)->toString();
            }
        }

        return '';
    }
}
