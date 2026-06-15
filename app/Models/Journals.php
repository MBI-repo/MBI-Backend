<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Journals extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'publication_year',
        'authors',
        'access_type',
        'is_restricted',
        'abstract',
        'introduction',
        'publication_url',
        'document_path',
        'tags',
    ];

    protected $casts = [
        'publication_year' => 'integer',
        'is_restricted' => 'boolean',
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

