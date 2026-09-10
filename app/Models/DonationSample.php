<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class DonationSample extends Model
{
    protected $table = 'donation_samples';

    protected $fillable = [
        'uuid',
        'donation_uuid',
        'sample_number',
        'sample_type',
        'collected_at',
        'received_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'received_at'  => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | UUID
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }



    public function donation()
    {
        return $this->belongsTo(
            Donation::class,
            'donation_uuid',
            'uuid'
        );
    }
}