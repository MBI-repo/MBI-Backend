<?php

namespace App\Console\Commands;

use App\Mail\EventReminderMail;
use App\Models\Event;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


class SendEventReminderNotifications extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Create platform and email notifications one day before events.';

    public function handle(): int
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        $events = Event::with(['subscriptions.user', 'organizer'])
            ->where('status', 'published')
            ->where('start_date', $tomorrow)
            ->whereNull('reminder_sent_at')
            ->get();

        $notificationsCreated = 0;
        $emailsSent = 0;
        $emailsFailed = 0;

        foreach ($events as $event) {
            $receivers = collect();

            if ($event->organizer) {
                $receivers->push($event->organizer);
            }

            foreach ($event->subscriptions as $subscription) {
                if ($subscription->user) {
                    $receivers->push($subscription->user);
                }
            }

            $receivers = $receivers->unique('id');

            foreach ($receivers as $user) {
                Notification::create([
                    'receiver_id' => $user->uuid,
                    'sender_id' => $event->organizer?->uuid,
                    'title' => 'Event reminder',
                    'message' => "{$event->title} is scheduled for tomorrow.",
                    'type' => 'event_reminder',
                    'is_read' => false,
                    'reference_id' => $event->id,
                    'reference_type' => 'event',
                ]);
                if (! empty($user->email)) {
                    try {
                        Mail::to($user->email)->send(new EventReminderMail($event));
                        $emailsSent++;
                    } catch (\Throwable $e) {
                        $emailsFailed++;

                        Log::error('Failed to send event reminder email.', [
                            'event_id' => $event->id,
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                // if (! empty($user->phone)) {
                //     // Send SMS notification
                // }

                $notificationsCreated++;
            }

            $event->update([
                'reminder_sent_at' => now(),
            ]);
        }

        $this->info("Checked date: {$tomorrow}");
        $this->info("Events processed: {$events->count()}");
        $this->info("Notifications created: {$notificationsCreated}");
        $this->info("Emails sent: {$emailsSent}");
        $this->info("Emails failed: {$emailsFailed}");

        return self::SUCCESS;
    }


}
