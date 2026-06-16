<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabFacility extends Model
{
    protected $fillable = [
        'name',
        'label',
        'description',
        'icon',
    ];

    public function testCategories()
    {
        return $this->hasMany(TestCategory::class, 'lab_facility_id');
    }
}
