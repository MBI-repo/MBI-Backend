<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EventReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Event $event)
    {
    }

    public function build()
    {
        return $this->subject('Reminder: ' . $this->event->title . ' is tomorrow')
            ->view('emails.event-reminder');
    }
}
