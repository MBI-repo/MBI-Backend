<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloodComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'blood_bank_uuid',
        'donor_uuid',
        'screening_uuid',
        'blood_collection_uuid',
        'laboratory_test_uuid',
        'donation_id',
        'component_type',
        'component_type',
        'component_id',   
        'volume',
        'volume_unit',
        'blood_group',
        'expiry_date',
        'storage_type',
        'status',
    ];

    protected $casts = [
        'volume' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function donation(): BelongsTo
    {
        return $this->belongsTo(
            Donation::class,
            'donation_uuid',
            'uuid'
        );
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

    public function laboratoryTest()
    {
        return $this->belongsTo(
            LabTest::class,
            'laboratory_test_uuid',
            'uuid'
        );
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(
            InventoryTransaction::class,
            'blood_component_uuid',
            'uuid'
        );
    }

    public function reservations()
    {
        return $this->hasMany(
            InventoryReservation::class,
            'blood_component_uuid',
            'uuid'
        );
    }

    public function activeReservation()
    {
        return $this->hasOne(
            InventoryReservation::class,
            'blood_component_uuid',
            'uuid'
        )->where('status', 'active');
    }

    
}
