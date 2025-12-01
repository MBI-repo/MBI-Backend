<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'full_name',
        'email',
        'phone',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

