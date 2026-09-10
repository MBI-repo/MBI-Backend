<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;



class BloodBank extends Model
{
    use HasFactory;

    protected $table = 'blood_bank';

    protected $fillable = [
        'uuid',
        'user_uuid',
        'name',
        'registration_number',
        'license_number',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'logo',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

    public function donors()
    {
        return $this->hasMany(Donor::class, 'blood_bank_uuid', 'uuid');
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function bloodUnits()
    {
        return $this->hasMany(BloodUnit::class);
    }

    public function equipment()
    {
        return $this->hasMany(Equipment::class, 'blood_bank_uuid', 'uuid');
    }

    public function requests()
    {
        return $this->hasMany(BloodRequest::class,'blood_bank_uuid','uuid');
    }
    public function bloodCollections()
    {
        return $this->hasMany(
            BloodCollection::class,
            'blood_bank_uuid',
            'uuid'
        );
    }
}