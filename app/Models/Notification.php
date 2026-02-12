<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Notification extends Model
{
    use HasFactory;

    protected $fillable = [

        'receiver_id',
        'sender_id',
        'title',
        'message',
        'type',
        'is_read',
        'reference_id',
        'reference_type',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // Receiver
   public function receiver()
    {
    return $this->belongsTo(User::class, 'receiver_id', 'uuid');
    }

    // Sender
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'uuid');
    }
}
