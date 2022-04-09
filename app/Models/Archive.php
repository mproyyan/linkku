<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Archive extends Model
{
    use HasFactory, HasSlug;

    const PUBLIC = 1;
    const PRIVATE = 2;

    protected $fillable = ['user_id', 'title', 'slug', 'description', 'excerpt', 'views', 'visibility'];

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

    public function links()
    {
        return $this->belongsToMany(Link::class, 'archive_link')
            ->withPivot('created_at');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function type()
    {
        return $this->belongsTo(Visibility::class, 'visibility');
    }
}
