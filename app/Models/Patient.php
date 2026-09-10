<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $table = 'patients';

    protected $fillable = [
        'uuid',
        'user_uuid',
        'facility_uuid',
        'doctor_uuid',
        'patient_number',
        'full_name',
        'date_of_birth',
        'gender',
        'blood_group',
        'genotype',
        'contact',
        'email',
        'address',
        'department',
        'diagnosis',
        'allergies',
        'medical_notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function bloodRequests()
    {
        return $this->hasMany(
            BloodRequest::class,
            'patient_uuid',
            'uuid'
        );
    }
    

    public function transfusions()
    {
        return $this->hasMany(
            Transfusion::class,
            'patient_uuid',
            'uuid'
        );
    }
    
    public function doctor()
    {
        return $this->belongsTo(
            DoctorProfile::class,
            'doctor_uuid',
            'uuid'
        );
    }
}