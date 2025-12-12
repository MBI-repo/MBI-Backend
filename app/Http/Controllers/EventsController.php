<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EventsController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::query();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }
        if (!is_null($request->query('is_online'))) {
            $query->where('is_online', filter_var($request->query('is_online'), FILTER_VALIDATE_BOOLEAN));
        }
        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%$q%")
                    ->orWhere('description', 'like', "%$q%")
                    ->orWhere('venue_name', 'like', "%$q%")
                    ->orWhere('city', 'like', "%$q%");
            });
        }
        if ($from = $request->query('from')) {
            $query->where('start_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->where('start_at', '<=', $to);
        }

        $events = $query->orderBy('start_at')->get();

        return response()->json(['events' => $events]);
    }

    public function show($id)
    {
        try {
            // Support numeric id or slug/uuid
            if (is_numeric($id)) {
                $event = Event::find($id);
            } else {
                $event = Event::where('slug', $id)->first();
                if (!$event) {
                    // Attempt uuid if present in schema (harmless if not)
                    $event = Event::where('uuid', $id)->first();
                }
            }

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }

            $base_url = "https://admin.mybridgeinternational.org/mbi-admin-files/public/";

            // Ensure full image URL only when it's a relative path
            $img = $event->image_url;
            if (!empty($img) && !str_starts_with($img, 'http://') && !str_starts_with($img, 'https://')) {
                $event->image_url = $base_url . ltrim($img, '/');
            }

            return response()->json([
                'success' => true,
                'message' => 'Event fetched successfully',
                'event' => $event
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event fetch failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function fetchAll(Request $request)
    {
        try {
            if ($request->has('start_date')) {
                $events = Event::where('start_date', '=', $request->input('start_date'))->get();
            } else {

                $events = Event::latest()->get();
            }
            $base_url = "https://admin.mybridgeinternational.org/mbi-admin-files/public/";


            // Loop through each event and modify image_url
            $events->transform(function ($event) use ($base_url) {
                $img = $event->image_url;
                if (!empty($img) && !str_starts_with($img, 'http://') && !str_starts_with($img, 'https://')) {
                    $event->image_url = $base_url . ltrim($img, '/');
                }
                return $event;
            });


            return response()->json([
                'success' => true,
                'message' => 'Event fetched successfully',
                'events' => $events
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event fetch failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable'],
            'is_online' => ['nullable', 'boolean'],
            'meeting_link' => ['nullable', 'url'],
            'status' => ['nullable', 'in:draft,published,cancelled,upcoming,completed'],
            'start_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'visibility' => ['nullable', 'boolean'],
            'venue' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'tags' => ['nullable'],
        ]);

        // Ensure end datetime is not before start datetime when both are provided
        $validator->after(function ($v) use ($request) {
            $sd = $request->input('start_date');
            $st = $request->input('start_time');
            $ed = $request->input('end_date');
            $et = $request->input('end_time');
            if ($sd && $st && $ed) {
                try {
                    $start = Carbon::parse($sd . ' ' . $st);
                    $end = Carbon::parse($ed . ' ' . ($et ?? '00:00'));
                    if ($end->lt($start)) {
                        $v->errors()->add('end_date', 'End datetime must be after or equal to start datetime.');
                    }
                } catch (\Exception $e) {
                    // Ignore parse errors; base rules will handle format issues
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $startAt = Carbon::parse($data['start_date'] . ' ' . $data['start_time']);
        $endAt = null;
        if (!empty($data['end_date'])) {
            $endAt = Carbon::parse($data['end_date'] . ' ' . ($data['end_time'] ?? '00:00'));
        }

        if ($request->has('image')) {
        }

        $event = Event::create([
            'organizer_id' => $user ? $user->id : null,
            'title' => $data['title'],
            'slug' => $this->generateUniqueSlug($data['title']),
            'category' => $data['category'] ?? null,
            'description' => $data['description'] ?? null,
            'is_online' => $data['is_online'] ?? false,
            'meeting_link' => $data['meeting_link'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'visibility' => $data['visibility'] ?? true,
            'start_date' => $startAt->toDateString(),
            'end_date' => $endAt?->toDateString(),
            'start_time' => $startAt->toTimeString(),
            'end_time' => $endAt?->toTimeString(),
            'timezone' => $data['timezone'] ?? null,
            'venue' => $data['venue'] ?? null,
            'price' => $data['price'] ?? null,
            'tags' => $data['tags'] ?? null,
        ]);

        // Handle image upload to public folder and update image_url
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $uploadDir = public_path('uploads/events');
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            $ext = $file->getClientOriginalExtension();
            $baseName = Str::slug($event->title);
            $fileName = $baseName . '-' . time() . ($ext ? '.' . $ext : '');
            $file->move($uploadDir, $fileName);

            $event->image_url = '/uploads/events/' . $fileName;
            $event->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully',
            'event' => $event
        ], 201);
    }

    public function update($id, Request $request)
    {
        $event = Event::findOrFail($id);
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'title' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable'],
            'is_online' => ['nullable', 'boolean'],
            'meeting_link' => ['nullable', 'url'],
            'status' => ['nullable', 'in:draft,published,cancelled,upcoming,completed'],
            'visibility' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'venue' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'tags' => ['nullable', 'array'],
        ]);

        $validator->after(function ($v) use ($request) {
            $sd = $request->input('start_date');
            $st = $request->input('start_time');
            $ed = $request->input('end_date');
            $et = $request->input('end_time');
            if ($sd && $st && $ed) {
                try {
                    $start = Carbon::parse($sd . ' ' . $st);
                    $end = Carbon::parse($ed . ' ' . ($et ?? '00:00'));
                    if ($end->lt($start)) {
                        $v->errors()->add('end_date', 'End datetime must be after or equal to start datetime.');
                    }
                } catch (\Exception $e) {
                    // ignore
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (!empty($data['title']) && $data['title'] !== $event->title) {
            $event->slug = $this->generateUniqueSlug($data['title']);
        }

        // Merge date/time to datetimes if provided
        $startAt = $event->start_at;
        $endAt = $event->end_at;
        if (!empty($data['start_date']) && !empty($data['start_time'])) {
            $startAt = Carbon::parse($data['start_date'] . ' ' . $data['start_time']);
        }
        if (!empty($data['end_date'])) {
            $endAt = Carbon::parse($data['end_date'] . ' ' . ($data['end_time'] ?? '00:00'));
        }

        $event->fill([
            'title' => $data['title'] ?? $event->title,
            'category' => $data['category'] ?? $event->category,
            'description' => $data['description'] ?? $event->description,
            'is_online' => $data['is_online'] ?? $event->is_online,
            'meeting_link' => $data['meeting_link'] ?? $event->meeting_link,
            'status' => $data['status'] ?? $event->status,
            'visibility' => $data['visibility'] ?? $event->visibility,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'timezone' => $data['timezone'] ?? $event->timezone,
            'venue' => $data['venue'] ?? $event->venue,
            'price' => $data['price'] ?? $event->price,
            'tags' => $data['tags'] ?? $event->tags,
        ]);

        // Optional image upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $uploadDir = public_path('uploads/events');
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $ext = $file->getClientOriginalExtension();
            $baseName = Str::slug($event->title);
            $fileName = $baseName . '-' . time() . ($ext ? '.' . $ext : '');
            $file->move($uploadDir, $fileName);
            $event->image_url = '/uploads/events/' . $fileName;
        }

        $event->save();

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully',
            'event' => $event
        ]);
    }



    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();
        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully',
            'eventId' => (string)$id,
            'status' => 'deleted'
        ]);
    }

    private function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;
        while (Event::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    public function subscribe(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $user = $request->user();

        $data = $request->validate([
            'full_name' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
        ]);

        $exists = \App\Models\EventSubscription::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Already subscribed to this event',
            ], 409);
        }

        $subscription = \App\Models\EventSubscription::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'full_name' => $data['full_name'] ?? $user->full_name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscribed to event successfully',
            'event' => $event,
            'subscription' => $subscription,
        ]);
    }
}
