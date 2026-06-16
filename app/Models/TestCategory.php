<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'lab_facility_id',
    ];

    public function facility()
    {
        return $this->belongsTo(LabFacility::class, 'lab_facility_id');
    }

    public function labOrders()
    {
        return $this->hasMany(LabOrder::class);
    }
}
