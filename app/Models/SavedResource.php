<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SavedResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'savable_id',
        'savable_type',
    ];

    /**
     * Get the parent savable model (journal, article, or r&d).
     */
    public function savable(): MorphTo
    {
        return $this->morphTo();
    }
}
