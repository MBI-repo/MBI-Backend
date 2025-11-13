<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'status', // pending, accepted, rejected, blocked
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

     public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
