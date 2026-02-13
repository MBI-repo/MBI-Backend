<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Notificationsettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_uuid',
        'network',
        'messages',
        'events',
        'system',
        'frequency',
    ];

    protected $casts = [
        'network'  => 'boolean',
        'messages' => 'boolean',
        'events'   => 'boolean',
        'system'   => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }
}
