<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Articles extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'status',
        'publication_year',
        'authors',
        'access_type',
        'is_restricted',
        'abstract',
        'introduction',
        'body',
        'image_url',
        'no_of_likes',
        'no_of_comments',
        'publication_url',
        'tags',
    ];

    protected $casts = [
        'publication_year' => 'integer',
        'is_restricted' => 'boolean',
        'no_of_likes' => 'integer',
        'no_of_comments' => 'integer',
        'tags' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all of the resource's saves.
     */
    public function savedBy()
    {
        return $this->morphMany(SavedResource::class, 'savable');
    }
}
