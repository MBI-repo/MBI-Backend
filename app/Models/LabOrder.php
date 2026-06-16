<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrder extends Model
{
    protected $fillable = [
        'order_id',
        'patient_id',
        'lab_facility_id',
        'test_category_id',
        'specific_test_name',
        'priority',
        'lab_center_id',
        'provisional_diagnosis',
        'clinical_notes',
        'attachment_path',
        'status',
        'frequency',
        'duration',
        'start_date',
        'end_date',
        'override_justification',
    ];

    public function result()
    {
        return $this->hasOne(LabResult::class, 'lab_order_id', 'id');
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function facility()
    {
        return $this->belongsTo(LabFacility::class, 'lab_facility_id');
    }

    public function category()
    {
        return $this->belongsTo(TestCategory::class, 'test_category_id');
    }

    public function labCenter()
    {
        return $this->belongsTo(LabCenter::class, 'lab_center_id');
    }
}
