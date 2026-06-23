<?php

namespace App\Models;

use App\Models\Connection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'image',
        'status',
        'lastSeen',
        'full_name',
        'email',
        'phone',
        'password',
        'category',
        'specialisation',
        'institution',
        'license_number',
        'approval_status',
        'country',
        'facility_name',
        'medical_licence',
        'isSeller',
        'kyc_verified_at',
        'gender',
        'dob',
        'city',
        'state',
        'user_type',
    ];

    public function sentConnections()
    {
        return $this->hasMany(Connection::class, 'sender_id');
    }

    public function receivedConnections()
    {
        return $this->hasMany(Connection::class, 'receiver_id');
    }

    public function acceptedConnections()
    {
        return Connection::where(function ($query) {
            $query->where('sender_id', $this->id)
                  ->orWhere('receiver_id', $this->id);
        })->where('status', 'accepted');
    }

    public function pendingConnections()
    {
        return Connection::where(function ($query) {
            $query->where('sender_id', $this->id)
                  ->orWhere('receiver_id', $this->id);
        })->where('status', 'pending');
    }

    // for one-to-one relationship with ProfessionalProfile
    public function professionalProfile()
    {
        return $this->hasOne(ProfessionalProfile::class, 'user_uuid', 'uuid');
    }


    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'isSeller' => 'boolean',
            'kyc_verified_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
