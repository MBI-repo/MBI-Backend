<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\TelemedicineSession;
use App\Models\User;
use App\Events\TelemedicineMessageSent;
use App\Events\TelemedicineSessionCompleted;
use App\Events\InboxUpdated;
use App\Events\TelemedicineCallSignal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TelemedicineSessionController extends Controller
{
    public function doctors()
    {
        $doctors = User::where('user_type', 'doctor')
            ->orWhere(function ($q) {
                $q->whereNotIn('category', ['patient', 'user'])
                  ->whereNotNull('category')
                  ->where('category', '!=', '');
            })
            ->get(['id', 'uuid', 'full_name', 'email', 'category', 'specialisation', 'institution']);

        return response()->json([
            'status' => true,
            'data' => $doctors
        ]);
    }

    /**
     * Create a new telemedicine session.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:users,id',
            'symptoms' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // 1. Create a Conversation of type 'direct'
        $conv = Conversation::create([
            'type' => 'direct',
            'name' => null,
            'creator_id' => $user->id,
        ]);

        // 2. Link participants to the conversation pivot
        $conv->participants()->attach($user->id, ['is_admin' => false]);
        $conv->participants()->attach($validated['doctor_id'], ['is_admin' => false]);

        // 3. Create Telemedicine Session
        $session = TelemedicineSession::create([
            'patient_id' => $user->id,
            'doctor_id' => $validated['doctor_id'],
            'conversation_id' => $conv->id,
            'symptoms' => $validated['symptoms'] ?? 'Consultation request',
            'status' => 'active',
        ]);

        // 4. Create starting message if symptoms are provided
        if (!empty($validated['symptoms'])) {
            $msg = Message::create([
                'conversation_id' => $conv->id,
                'sender_id' => $user->id,
                'message_type' => 'text',
                'content' => "Symptom Description: " . $validated['symptoms'],
            ]);
        }

        $session->load(['doctor', 'patient']);

        // 5. Broadcast inbox updates
        foreach ($conv->participants as $p) {
            $other = $conv->participants->firstWhere('id', '!=', $p->id);
            $item = [
                'conversationId' => $conv->id,
                'conversationUuid' => $conv->uuid,
                'type' => $conv->type,
                'name' => $conv->name,
                'updatedAt' => $conv->updated_at,
                'lastMessage' => isset($msg) ? [
                    'id' => $msg->id,
                    'uuid' => $msg->uuid,
                    'type' => $msg->message_type,
                    'content' => $msg->content,
                    'createdAt' => $msg->created_at,
                    'senderId' => $msg->sender_id,
                ] : null,
                'otherParticipant' => $other ? [
                    'id' => $other->id,
                    'uuid' => $other->uuid,
                    'full_name' => $other->full_name,
                    'email' => $other->email,
                ] : null,
            ];
            try {
                broadcast(new InboxUpdated($p->id, (string)$p->uuid, $item))->toOthers();
            } catch (\Throwable $e) {
                // Ignore broadcasting failures on local setup
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Telemedicine session initiated successfully',
            'data' => [
                'sessionId' => $session->uuid,
                'conversationId' => $conv->uuid,
            ]
        ], 201);
    }

    /**
     * List sessions for patients.
     */
    public function patientSessions()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $sessions = TelemedicineSession::with(['doctor', 'conversation.messages' => function ($q) {
                $q->latest('created_at')->limit(1);
            }])
            ->where('patient_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $mapped = $sessions->map(function ($s) {
            $lastMsg = $s->conversation->messages->first();
            return [
                'id' => $s->uuid,
                'doctorName' => $s->doctor->full_name ?? 'Doctor',
                'doctorSpecialty' => $s->doctor->specialisation ?? 'General Practitioner',
                'status' => $s->status,
                'lastMessage' => $lastMsg ? $lastMsg->content : 'Consultation started',
                'lastMessageAt' => ($lastMsg ? $lastMsg->created_at : $s->created_at)->toISOString(),
                'unreadCount' => 0,
            ];
        });

        return response()->json($mapped);
    }

    /**
     * List sessions for doctors.
     */
    public function doctorSessions()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $sessions = TelemedicineSession::with(['patient', 'conversation.messages' => function ($q) {
                $q->latest('created_at')->limit(1);
            }])
            ->where('doctor_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $mapped = $sessions->map(function ($s) {
            $lastMsg = $s->conversation->messages->first();
            return [
                'id' => $s->uuid,
                'patientId' => (string)$s->patient_id,
                'patientName' => $s->patient->full_name ?? 'Patient',
                'patientEmail' => $s->patient->email ?? '',
                'symptoms' => $s->symptoms,
                'status' => $s->status,
                'lastMessage' => $lastMsg ? $lastMsg->content : 'Consultation started',
                'lastMessageAt' => ($lastMsg ? $lastMsg->created_at : $s->created_at)->toISOString(),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $mapped
        ]);
    }

    /**
     * Show session details.
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $session = TelemedicineSession::with(['patient', 'doctor', 'conversation'])
            ->where('uuid', $id)
            ->first();

        if (!$session) {
            return response()->json(['status' => false, 'message' => 'Session not found'], 404);
        }

        if ($session->patient_id !== $user->id && $session->doctor_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $mapped = [
            'id' => $session->uuid,
            'patientId' => (string)$session->patient_id,
            'doctorName' => $session->doctor->full_name ?? 'Doctor',
            'doctorSpecialty' => $session->doctor->specialisation ?? 'General Practitioner',
            'patientName' => $session->patient->full_name ?? 'Patient',
            'status' => $session->status,
            'symptoms' => $session->symptoms,
            'conversationUuid' => $session->conversation->uuid,
            'lastMessage' => '',
            'lastMessageAt' => $session->updated_at->toISOString(),
            'unreadCount' => 0,
        ];

        return response()->json($mapped);
    }

    /**
     * Get chat messages history for a session.
     */
    public function messages($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $session = TelemedicineSession::where('uuid', $id)->first();
        if (!$session) {
            return response()->json(['status' => false, 'message' => 'Session not found'], 404);
        }

        if ($session->patient_id !== $user->id && $session->doctor_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $messages = Message::where('conversation_id', $session->conversation_id)
            ->orderBy('created_at', 'asc')
            ->get();

        $mapped = $messages->map(function ($m) use ($session) {
            $senderType = ($m->sender_id === $session->patient_id) ? 'patient' : 'doctor';
            return [
                'id' => (string)$m->id,
                'sender' => $senderType,
                'type' => $m->message_type === 'file' ? 'document' : $m->message_type,
                'content' => $m->content ?? '',
                'fileUrl' => $m->file_url ? $this->toAbsoluteUrl($m->file_url) : null,
                'fileName' => $m->file_url ? basename($m->file_url) : null,
                'createdAt' => $m->created_at->toISOString(),
            ];
        });

        return response()->json($mapped);
    }

    /**
     * Send message in session chat.
     */
    public function sendMessage(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $session = TelemedicineSession::where('uuid', $id)->first();
        if (!$session) {
            return response()->json(['status' => false, 'message' => 'Session not found'], 404);
        }

        if ($session->patient_id !== $user->id && $session->doctor_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        if ($session->status === 'ended') {
            return response()->json(['status' => false, 'message' => 'This session has ended.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:text,image,document',
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:10240', // max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        $storedFileUrl = null;
        $storedFileMime = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $basePublicRoot = public_path('mybridge-backend-files/public');
            $uploadDir = is_dir($basePublicRoot)
                ? $basePublicRoot . '/uploads/messages'
                : public_path('uploads/messages');
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $ext = $file->getClientOriginalExtension();
            $baseName = 'msg-' . $session->conversation_id . '-' . time() . '-' . rand(1000, 9999);
            $fileName = $baseName . ($ext ? '.' . $ext : '');
            $file->move($uploadDir, $fileName);
            $storedFileUrl = '/uploads/messages/' . $fileName;
            $storedFileMime = $file->getClientMimeType();
        }

        $msg = Message::create([
            'conversation_id' => $session->conversation_id,
            'sender_id' => $user->id,
            'message_type' => $validated['type'] === 'document' ? 'file' : $validated['type'],
            'content' => $validated['content'] ?? ($request->hasFile('file') ? $file->getClientOriginalName() : ''),
            'file_url' => $storedFileUrl,
            'file_mime_type' => $storedFileMime,
        ]);

        $senderType = ($msg->sender_id === $session->patient_id) ? 'patient' : 'doctor';
        $formatted = [
            'id' => (string)$msg->id,
            'sender' => $senderType,
            'type' => $validated['type'],
            'content' => $msg->content ?? '',
            'fileUrl' => $msg->file_url ? $this->toAbsoluteUrl($msg->file_url) : null,
            'fileName' => $msg->file_url ? basename($msg->file_url) : null,
            'createdAt' => $msg->created_at->toISOString(),
        ];

        // Broadcast to Pusher private-session channel
        try {
            broadcast(new TelemedicineMessageSent($session->uuid, $formatted))->toOthers();
        } catch (\Throwable $e) {
            // Ignore socket broadcast errors locally
        }

        return response()->json($formatted, 201);
    }

    /**
     * End a telemedicine session.
     */
    public function end($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $session = TelemedicineSession::where('uuid', $id)->first();
        if (!$session) {
            return response()->json(['status' => false, 'message' => 'Session not found'], 404);
        }

        // Only the doctor associated with the session can end it
        if ($session->doctor_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Forbidden. Only the physician can end the session.'], 403);
        }

        $session->update([
            'status' => 'ended'
        ]);

        // Broadcast completion event
        try {
            broadcast(new TelemedicineSessionCompleted($session->uuid))->toOthers();
        } catch (\Throwable $e) {
            // Ignore broadcast failure locally
        }

        return response()->json([
            'status' => true,
            'message' => 'Session ended successfully'
        ]);
    }

    /**
     * Send call signal to the other party in the session.
     */
    public function sendCallSignal(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $session = TelemedicineSession::where('uuid', $id)->first();
        if (!$session) {
            return response()->json(['status' => false, 'message' => 'Session not found'], 404);
        }

        if ($session->patient_id !== $user->id && $session->doctor_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:audio,video',
            'action' => 'required|in:started,accepted,declined,ended',
            'sender' => 'required|in:patient,doctor',
            'duration' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        try {
            broadcast(new TelemedicineCallSignal($session->uuid, $validated))->toOthers();
        } catch (\Throwable $e) {
            // Ignore broadcast failure locally
        }

        $chatMessage = null;
        if ($validated['action'] === 'declined' || $validated['action'] === 'ended') {
            $typeStr = ucfirst($validated['type']);
            $emoji = $validated['type'] === 'video' ? '📹' : '📞';

            if ($validated['action'] === 'declined') {
                $content = "{$emoji} Missed {$validated['type']} call";
            } else {
                if (!empty($request->input('duration'))) {
                    $content = "{$emoji} {$typeStr} call ended • " . $request->input('duration');
                } else {
                    $content = "{$emoji} Missed {$validated['type']} call";
                }
            }

            // Create Message
            $msg = \App\Models\Message::create([
                'conversation_id' => $session->conversation_id,
                'sender_id' => $user->id,
                'message_type' => 'text',
                'content' => $content,
            ]);

            $senderType = ($msg->sender_id === $session->patient_id) ? 'patient' : 'doctor';
            $formatted = [
                'id' => (string)$msg->id,
                'sender' => $senderType,
                'type' => 'text',
                'content' => $msg->content ?? '',
                'createdAt' => $msg->created_at->toISOString(),
            ];

            try {
                broadcast(new \App\Events\TelemedicineMessageSent($session->uuid, $formatted))->toOthers();
            } catch (\Throwable $e) {
                // Ignore socket broadcast errors locally
            }

            $chatMessage = $formatted;
        }

        return response()->json([
            'status' => true,
            'message' => 'Signal sent successfully',
            'chatMessage' => $chatMessage
        ]);
    }

    private function toAbsoluteUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }
        return url($path);
    }
}

