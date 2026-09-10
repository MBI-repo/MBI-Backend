<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BloodRequest extends Model
{
    protected $table = 'blood_request';

    protected $fillable = [
        'uuid',
        'blood_bank_uuid',
        'facility_uuid',
        'patient_uuid',
        'doctor_uuid',
        'full_name',
        'request_number',
        'request_date',
        'request_time',
        'priority',
        'blood_group',
        'note',
        'required_at',
        'rejection_reason',
        'component_type',
        'unit_needed',
        'department',
        'clinical_reason',
        'status',

    ];

    protected $casts = [
        'required_at' => 'datetime',
        'request_date' => 'date',
        'request_time' => 'datetime',

    ];

    public function user()
    {
        return $this->hasMany(User::class, 'user_uuid','uuid');
    }
    public function bloodBank()
    {
        return $this->hasMany(BloodBank::class, 'blood_bank_uuid','uuid');
    }
    public function facility()
    {
        return $this->hasMany(Facility::class, 'facility_uuid','uuid');
    }
    // public function patient()
    // {
    //     return $this->hasMany(Patient::class, 'patient_uuid','uuid');
    // }

   
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_uuid', 'uuid');
    }

    public function doctor()
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_uuid','uuid');  
    }
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_uuid','uuid');
    }

    public function transfusions()
    {
        return $this->belongsTo(
            Transfusion::class,
            'blood_request_uuid',
            'uuid'
        );
    }


}