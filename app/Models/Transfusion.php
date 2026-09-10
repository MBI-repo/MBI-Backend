<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfusion extends Model
{
    use HasFactory;

    protected $table = 'transfusion';

    protected $fillable = [
        'uuid',
        'patient_uuid',
        'doctor_uuid',
        'blood_request_uuid',
        'blood_component_uuid',
        'transfusion_number',
        'blood_group',
        'component_type',
        'blood_unit_id',
        'start_time',
        'proposed_end_time',
        'actual_end_time',
        'assigned_staff_uuid',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'proposed_end_time' => 'datetime',
            'actual_end_time' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Patient
    |--------------------------------------------------------------------------
    */

    public function patient()
    {
        return $this->belongsTo(
            Patient::class,
            'patient_uuid',
            'uuid'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Doctor
    |--------------------------------------------------------------------------
    */

    public function doctor()
    {
        return $this->belongsTo(
            DoctorProfile::class,
            'doctor_uuid',
            'uuid'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Blood Request
    |--------------------------------------------------------------------------
    */

    public function bloodRequest()
    {
        return $this->belongsTo(
            BloodRequest::class,
            'blood_request_uuid',
            'uuid'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Blood Component
    |--------------------------------------------------------------------------
    */

    public function bloodComponent()
    {
        return $this->belongsTo(
            BloodComponent::class,
            'blood_component_uuid',
            'uuid'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Assigned Staff
    |--------------------------------------------------------------------------
    */

    public function assignedStaff()
    {
        return $this->belongsTo(
            User::class,
            'assigned_staff_uuid',
            'uuid'
        );
    }
} 