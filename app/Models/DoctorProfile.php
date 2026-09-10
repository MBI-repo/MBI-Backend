<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class DoctorProfile extends Model
{
    use HasFactory;

    protected $table = 'doctor_profiles';

    protected $fillable = [
        'uuid',
        'user_uuid',
        'title',
        'license_number',
        'specialization',
        'qualification',
        'years_of_experience',
        'medical_council',
        'license_document',
        'identification_document',
        'address',
        'state',
        'country',
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

    public function patients()
    {
        return $this->hasMany(Patient::class, 'doctor_uuid', 'uuid');
    }
}