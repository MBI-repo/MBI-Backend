<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;

class InventoryReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'blood_bank_uuid',
        'blood_component_uuid',
        'blood_request_uuid',
        'reserved_by_uuid',
        'reserved_at',
        'expires_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'reserved_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function bloodBank()
    {
        return $this->belongsTo(
            BloodBank::class,
            'blood_bank_uuid',
            'uuid'
        );
    }

    public function bloodComponent()
    {
        return $this->belongsTo(
            BloodComponent::class,
            'blood_component_uuid',
            'uuid'
        );
    }

    public function bloodRequest()
    {
        return $this->belongsTo(
            BloodRequest::class,
            'blood_request_uuid',
            'uuid'
        );
    }

    public function reservedBy()
    {
        return $this->belongsTo(
            User::class,
            'reserved_by_uuid',
            'uuid'
        );
    }
}