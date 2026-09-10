<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DoctorFacility extends Model
{
    use HasFactory;

    protected $table = 'doctor_facilities';

    protected $fillable = [
        'uuid',
        'doctor_uuid',
        'facility_uuid',
        'position',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    { 
        return[
        'start_date' => 'date',
        'end_date'   => 'date',
        ];
    }

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

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_uuid', 'uuid');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'facility_uuid', 'uuid');
    }
}