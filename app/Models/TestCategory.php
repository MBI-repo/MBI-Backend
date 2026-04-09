<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function labOrders()
    {
        return $this->hasMany(LabOrder::class);
    }
}
