<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Download extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'downloadable_id',
        'resource_type',
        'status',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the user who made the download.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
