<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonorScreening extends Model
{
    use HasFactory;

    protected $table = 'donor_screening';

    protected $fillable = [
        'uuid',
        'donor_uuid',
        'screening_date',

        'age',
        'weight',

        'systolic_bp',
        'diastolic_bp',

        'recent_fever_or_infection',
        'infectious_disease_exposure',
        'recent_tattoo_or_piercing',
        'high_risk_travel_history',

        'existing_medical_condition',
        'recent_surgery',
        'current_medication',
        'feeling_unwell',

        'eligibility',
        'deferral_reason',
        'deferred_until',
        'notes',

        'screened_by_uuid',
    ];

    protected function casts(): array
    {
        return [
            'screening_date' => 'datetime',
            'deferred_until' => 'date',

            'weight' => 'decimal:2',

            'recent_fever_or_infection' => 'boolean',
            'infectious_disease_exposure' => 'boolean',
            'recent_tattoo_or_piercing' => 'boolean',
            'high_risk_travel_history' => 'boolean',

            'existing_medical_condition' => 'boolean',
            'recent_surgery' => 'boolean',
            'current_medication' => 'boolean',
            'feeling_unwell' => 'boolean',
        ];
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(
            Donor::class,
            'donor_uuid',
            'uuid'
        );
    }

    public function screenedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'screened_by_uuid',
            'uuid'
        );
    }

    public function bloodCollection()
    {
        return $this->hasOne(
            BloodCollection::class,
            'screening_uuid',
            'uuid'
        );
    }
}