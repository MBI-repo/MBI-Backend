<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'organizer_id',
        'title',
        'slug',
        'category',
        'description',
        'image_url',
        'is_online',
        'meeting_link',
        'status',
        'visibility',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'timezone',
        'venue',
        'price',
        'tags',
        'reminder_sent_at',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'visibility' => 'boolean',
       'reminder_sent_at' => 'datetime',
        'tags' => 'array',
    ];

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function subscribers() {
        return $this->belongsToMany(User::class, 'event_subscribers');
    }

    public function subscriptions()
    {
        return $this->hasMany(EventSubscription::class);
    }
}
