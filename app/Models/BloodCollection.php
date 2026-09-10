<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloodCollection extends Model
{
    use HasFactory;

    protected $table = 'blood_collection';

    protected $fillable = [
        'uuid',
        'blood_bank_uuid',
        'donor_uuid',
        'screening_uuid',
        'collected_by_uuid',
        'label',
        'volume',
        'unit_id',
        'collected_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'volume' => 'decimal:2',
            'collected_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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

    public function screening()
    {
        return $this->belongsTo(
            DonorScreening::class,
            'screening_uuid',
            'uuid'
        );
    }

    public function collectedBy()
    {
        return $this->belongsTo(
            User::class,
            'collected_by_uuid',
            'uuid'
        );
    }


    public function laboratoryTest()
    {
        return $this->hasOne(LabTest::class, 'collection_uuid', 'uuid');
    }

    public function donation()
    {
        return $this->hasOne(
            Donation::class,
            'blood_collection_uuid',
            'uuid'
        );
    }

}
