<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabTest extends Model
{
    use HasFactory;

    protected $table = 'laboratory_tests';

    protected $fillable = [
        'uuid',
        'blood_bank_uuid',
        'donor_uuid',
        'screening_uuid',
        'collection_uuid',

        'abo_typing',
        'rh_factor',

        'hiv_result',
        'hiv_test_kit_used',

        'hepatitis_a',
        'hepatitis_b',

        'syphilis_result',
        'syphilis_test_kit_used',

        'is_safe',
    ];

    protected $casts = [
        'age' => 'integer',
        'registration_date' => 'date',
        'last_donation_date' => 'date',
        'is_safe' => 'boolean',
    ];

    public function donor()
    {
        return $this->belongsTo(Donor::class, 'donor_uuid', 'uuid');
    }

    public function screening()
    {
        return $this->belongsTo(DonorScreening::class, 'screening_uuid', 'uuid');
    }

    public function collection()
    {
        return $this->belongsTo(BloodCollection::class, 'collection_uuid', 'uuid');
    }

    public function bloodBank()
    {
        return $this->belongsTo(BloodBank::class, 'blood_bank_uuid', 'uuid');
    }
}
