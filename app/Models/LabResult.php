<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabResult extends Model
{
    protected $fillable = [
        'lab_order_id',
        'signed_off_by_name',
        'signed_off_by_title',
        'signed_off_by_gmc',
        'date_completed',
        'doctor_comment',
        'overall_flag',
        'status',
    ];

    public function order()
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }

    public function parameters()
    {
        return $this->hasMany(LabResultParameter::class);
    }

    public function equipment()
    {
        return $this->belongsToMany(LabEquipment::class, 'lab_result_equipment');
    }
}
