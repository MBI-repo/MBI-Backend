<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Equipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'equipment';

    protected $fillable = [
        'uuid',
        'blood_bank_uuid',
        'facility_uuid',
        'name',
        'equipment_code',
        'category',
        'purpose',
        'uses',
        'replacement',
        'frequency',
        'department',
        'trainning',
        'image',
        'manufacturer',
        'condition',
        'description',
    ];


    public function bloodBank(): BelongsTo
    {
        return $this->belongsTo(
            BloodBank::class,
            'blood_bank_uuid',
            'uuid'
        );
    }

    public function maintenances()
    {
        return $this->hasMany(
            EquipmentMaintenance::class,
            'equipment_uuid',
            'uuid'
        );
    }
}