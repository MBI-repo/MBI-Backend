<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    use HasFactory;

    protected $table = 'donations';

    protected $fillable = [
        'uuid',
        'blood_bank_uuid',
        'donor_uuid',
        'blood_collection_uuid',
        'donation_number',
        'unit_id',
        'blood_group',
        'source',
        'volume',
        'volume_unit',
        'donation_date',
        'status',
        'recorded_by_uuid',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'donation_date' => 'datetime',
            'volume' => 'decimal:2',
        ];
    }

    public function bloodBank()
    {
        return $this->belongsTo(
            BloodBank::class,
            'blood_bank_uuid',
            'uuid'
        );
    }

    public function donor()
    {
        return $this->belongsTo(
            Donor::class,
            'donor_uuid',
            'uuid'
        );
    }

    public function bloodCollection()
    {
        return $this->belongsTo(
            BloodCollection::class,
            'blood_collection_uuid',
            'uuid'
        );
    }

    public function recordedBy()
    {
        return $this->belongsTo(
            User::class,
            'recorded_by_uuid',
            'uuid'
        );
    }
}