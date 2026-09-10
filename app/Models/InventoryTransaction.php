<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InventoryTransaction extends Model
{
    protected $table = 'inventory_transaction';

    protected $fillable = [
        'uuid',
        'blood_bank_uuid',
        'blood_collection_uuid',
        'blood_component_uuid',
        'transaction_type',
        'blood_group',
        'component_type',
        'storage_type',
        'previous_status',
        'stock_status',
        'expiry_alert',
        'reference_type',
        'reference_uuid',
        'performed_by_uuid',
        'reason',
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

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function bloodBank(): BelongsTo
    {
        return $this->belongsTo(
            BloodBank::class,
            'blood_bank_uuid',
            'uuid'
        );
    }

    public function bloodCollection(): BelongsTo
    {
        return $this->belongsTo(
            BloodCollection::class,
            'blood_collection_uuid',
            'uuid'
        );
    }

    public function bloodComponent(): BelongsTo
    {
        return $this->belongsTo(
            BloodComponent::class,
            'blood_component_uuid',
            'uuid'
        );
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'performed_by_uuid',
            'uuid'
        );
    }

    
}