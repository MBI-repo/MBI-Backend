<h2>{{ $event->title }}</h2>

<p>This is a reminder that your event is scheduled for tomorrow.</p>

<p>
    <strong>Date:</strong> {{ $event->start_date }}<br>
    <strong>Time:</strong> {{ $event->start_time }}<br>

    @if($event->is_online)
        <strong>Meeting Link:</strong> {{ $event->meeting_link }}
    @else
        <strong>Venue:</strong> {{ $event->venue }}
    @endif
</p>
