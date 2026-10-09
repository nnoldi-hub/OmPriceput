<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'cover_image',
        'cover_image_alt',
        'cover_image_caption',
        'cover_image_credit',
        'gallery',
        'faq',
        'auto_internal_links',
        'author_id',
        'meta_title',
        'meta_description',
        'focus_keyword',
        'canonical_url',
        'meta_robots',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'status',
        'published_at',
    ];

    protected $casts = [
        'gallery' => 'array',
        'faq' => 'array',
        'auto_internal_links' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $appends = [
        'cover_image_url',
        'og_image_url',
        'twitter_image_url',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image ? Storage::disk('public')->url($this->cover_image) : null;
    }

    public function getOgImageUrlAttribute(): ?string
    {
        if ($this->og_image) {
            return Storage::disk('public')->url($this->og_image);
        }

        return $this->cover_image_url;
    }

    public function getTwitterImageUrlAttribute(): ?string
    {
        if ($this->twitter_image) {
            return Storage::disk('public')->url($this->twitter_image);
        }

        return $this->og_image_url;
    }
}
