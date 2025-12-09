<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Notifications extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_uuid',
        'notify_network',
        'notify_messages',
        'notify_events',
        'notify_system',
        'frequency',
    ];

    protected $casts = [
        'notify_network'  => 'boolean',
        'notify_messages' => 'boolean',
        'notify_events'   => 'boolean',
        'notify_system'   => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }
}
