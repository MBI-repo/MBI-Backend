<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabEquipment extends Model
{
    protected $fillable = [
        'name',
        'serial_number',
        'type',
        'lab_center_id',
        'manufacturer',
        'model_number',
    ];

    public function labCenter()
    {
        return $this->belongsTo(LabCenter::class);
    }

    public function results()
    {
        return $this->belongsToMany(LabResult::class, 'lab_result_equipment');
    }
}
