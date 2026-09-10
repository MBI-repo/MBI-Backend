<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;


class Facility extends Model
{
    use HasFactory;

    protected $table = 'facilities';

    protected $fillable = [
        'uuid',
        'user_uuid',
        'facility_name',
        'facility_type',
        'registration_number',
        'license_number',
        'license_document',
        'license_expiry_date',
        'contact_email',
        'contact_phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'logo',
        'description',
        'registration_status',
    ];

    
    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }
   
}