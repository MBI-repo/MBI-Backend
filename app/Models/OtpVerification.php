<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpVerification extends Model
{
    use HasFactory;

    protected $table = 'otp_verification';

    protected $fillable = [
        'uuid',
        'user_uuid',
        'identifier',
        'type',
        'otp_hash',
        'attempts',
        'expires_at',
        'verified_at',
        'last_sent_at',
    ];

    protected $hidden = [
        'otp_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_uuid',
            'uuid'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }

    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }
}