<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Link extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = ['has', 'title', 'slug', 'url', 'description', 'excerpt', 'clicks', 'views',];

    const VISIBILITY = [1, 2];
    const PUBLIC = 1;
    const PRIVATE = 2;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function type()
    {
        return $this->belongsTo(Visibility::class, 'visibility');
    }
}
