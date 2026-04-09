<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrder extends Model
{
    protected $fillable = [
        'order_id',
        'patient_id',
        'test_category_id',
        'specific_test_name',
        'priority',
        'lab_center_id',
        'provisional_diagnosis',
        'clinical_notes',
        'attachment_path',
        'status',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
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
