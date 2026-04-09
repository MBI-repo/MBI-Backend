<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabCenter extends Model
{
    protected $fillable = [
        'name',
        'address',
        'location',
        'tat',
        'certification',
        'services',
        'opening_hours',
    ];

    protected $casts = [
        'services' => 'array',
    ];

    public function labOrders()
    {
        return $this->hasMany(LabOrder::class);
    }
}
