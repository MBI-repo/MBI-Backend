<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabResultParameter extends Model
{
    protected $fillable = [
        'lab_result_id',
        'parameter_name',
        'result_value',
        'unit',
        'reference_range',
        'flag',
    ];

    public function result()
    {
        return $this->belongsTo(LabResult::class, 'lab_result_id');
    }
}
