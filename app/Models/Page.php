<?php

namespace App\Models;

use App\Cms\Languages\LanguageManager;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title', 'slug', 'locale', 'translation_of', 'is_published', 'show_in_nav', 'show_in_footer', 'nav_label',
    'position', 'meta_title', 'meta_description', 'og_image_id',
])]
class Page extends Model
{
    protected function casts(): array
    {
        return [
            'is_home' => 'boolean',
            'is_published' => 'boolean',
            'show_in_nav' => 'boolean',
            'show_in_footer' => 'boolean',
        ];
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class)->orderBy('position');
    }

    public function visibleBlocks(): HasMany
    {
        return $this->blocks()->where('is_visible', true);
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_image_id');
    }

    public function original(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'translation_of');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function scopeInLocale(Builder $query, string $locale): void
    {
        $query->where('locale', $locale);
    }

    /** Identifier shared by a page and its translations. */
    public function translationGroup(): int
    {
        return $this->translation_of ?? $this->id;
    }

    /** @return Collection<int, Page> The page and all its translations */
    public function translations(): Collection
    {
        $group = $this->translationGroup();

        return static::where('id', $group)->orWhere('translation_of', $group)->get();
    }

    public function url(): string
    {
        return site_url($this->path());
    }

    public function path(): string
    {
        $prefix = app(LanguageManager::class)->pathPrefix($this->locale);

        return $this->is_home ? ($prefix ?: '/') : $prefix.'/'.$this->slug;
    }

    public function navLabel(): string
    {
        return $this->nav_label ?: $this->title;
    }
}
