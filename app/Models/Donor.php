<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class Donor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'donors';

    protected $fillable = [
        'uuid',
        'blood_bank_uuid',
        'donor_number',
        'full_name',
        'date_of_birth',
        'gender',
        'contact',
        'medical_history',
        'blood_group',
        'genotype',
        'email',
        'donor_type',
        'batch_number',
        'count',
        'existing_condition',
        'allergies',
        'donor_category',
        'patient_id',
        'source',
        'blood_type',
        'quantity',
        'collection_date',
        'last_donation_date',
        'eligibility',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'last_donation_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function bloodBank()
    {
        return $this->belongsTo(BloodBank::class,'blood_bank_uuid','uuid');
    }

    public function screenings()
    {
        return $this->hasMany(
            DonorScreening::class,
            'donor_uuid',
            'uuid'
        );
    }

    public function donations()
    {
        return $this->hasMany(Donation::class, 'donor_uuid','uuid');
    }

    public function bloodCollections()
    {
        return $this->hasMany(
            BloodCollection::class,
            'donor_uuid',
            'uuid'
        );
    }

}