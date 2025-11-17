<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ConversationsController extends Controller
{
    // POST /v1/conversations/direct
    public function startDirect(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'participantUuid' => ['nullable', 'string', 'exists:users,uuid'],
        ]);


        $participant = null;

        $participant = User::where('uuid', $data['participantUuid'])->firstOrFail();


        if ((int)$participant->id === (int)$user->id) {
            return response()->json(['message' => 'Cannot start a direct conversation with yourself'], 422);
        }

        // Try to find existing direct conversation between the two users
        $existing = Conversation::query()
            ->where('type', 'direct')
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->whereHas('participants', function ($q) use ($participant) {
                $q->where('users.id', $participant->id);
            })
            ->first();

        if (!$existing) {
            $conv = Conversation::create([
                'type' => 'direct',
                'name' => null,
                'creator_id' => $user->id,
            ]);
            $conv->participants()->attach($user->id, ['is_admin' => false]);
            $conv->participants()->attach($participant->id, ['is_admin' => false]);
            $existing = $conv;
        }

        return response()->json([
            'conversationId' => $existing->id,
            'conversationUuid' => $existing->uuid,
        ], 201);
    }
    // GET /v1/conversations
    public function index(Request $request)
    {
        $user = $request->user();
        $tab = $request->query('tab'); // optional: 'direct' | 'group'

        $query = Conversation::query()
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->with(['participants:id,full_name,email', 'messages' => function ($q) {
                $q->latest('created_at')->limit(1);
            }])
            ->orderByDesc('updated_at');

        if ($tab && in_array($tab, ['direct', 'group'])) {
            $query->where('type', $tab);
        }

        $conversations = $query->get()->map(function (Conversation $conv) {
            $last = $conv->messages->first();
            return [
                'id' => $conv->id,
                'uuid' => $conv->uuid,
                'type' => $conv->type,
                'name' => $conv->name,
                'updatedAt' => $conv->updated_at,
                'lastMessage' => $last ? [
                    'id' => $last->id,
                    'uuid' => $last->uuid,
                    'type' => $last->message_type,
                    'content' => $last->content,
                    'fileUrl' => $last->file_url,
                    'createdAt' => $last->created_at,
                ] : null,
                'participants' => $conv->participants->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'uuid' => $p->uuid,
                        'full_name' => $p->full_name,
                        'email' => $p->email,
                    ];
                }),
            ];
        });

        return response()->json([
            'conversations' => $conversations,
        ]);
    }

    // GET /v1/conversations/{conversationId}/messages
    public function messages(Request $request)
    {
        $user = $request->user();
        $conversationId = $request->conversationUuid;

        $limit = (int)($request->query('limit', 50));
        $beforeId = $request->query('before');

        $conversation = Conversation::with(['participants:id,full_name,email,uuid'])
            ->where('uuid', $conversationId)
            ->firstOrFail();

        // Authorization: must be a participant
        if (!$conversation->participants()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $messagesQuery = Message::where('conversation_id', $conversation->id)
            ->orderByDesc('id');

        if ($beforeId) {
            $messagesQuery->where('id', '<', $beforeId);
        }

        $messages = $messagesQuery->limit($limit)->get()->reverse()->values()->map(function (Message $m) {
            return [
                'id' => $m->id,
                'uuid' => $m->uuid,
                'conversationId' => $m->conversation_id,
                'senderId' => $m->sender_id,
                'messageType' => $m->message_type,
                'content' => $m->content,
                'fileData' => $m->file_url ? [
                    'url' => $m->file_url,
                    'mimeType' => $m->file_mime_type,
                ] : null,
                'readAt' => $m->read_at,
                'createdAt' => $m->created_at,
                'deletedAt' => $m->deleted_at,
            ];
        });

        $convData = [
            'id' => $conversation->id,
            'uuid' => $conversation->uuid,
            'type' => $conversation->type,
            'name' => $conversation->name,
            'updatedAt' => $conversation->updated_at,
            'participants' => $conversation->participants->map(function ($p) {
                return [
                    'id' => $p->id,
                    'uuid' => $p->uuid,
                    'full_name' => $p->full_name,
                    'email' => $p->email,
                ];
            }),
        ];

        return response()->json([
            'conversation' => $convData,
            'messages' => $messages,
        ]);
    }

    // GET /v1/conversations/{conversationId}/files
    public function files(Request $request)
    {
        $user = $request->user();
        $conversationId = $request->conversationUuid;
        $conversation = Conversation::where('uuid', $conversationId)->firstOrFail();

        if (!$conversation->participants()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $fileTypes = ['image', 'file', 'voicenote', 'video'];
        $files = Message::where('conversation_id', $conversation->id)
            ->whereIn('message_type', $fileTypes)
            ->whereNotNull('file_url')
            ->get()
            ->map(function (Message $m) {
                $path = parse_url($m->file_url, PHP_URL_PATH);
                $name = $path ? basename($path) : null;
                return [
                    'name' => $name,
                    'url' => $m->file_url,
                    'mimeType' => $m->file_mime_type,
                    'sender' => $m->sender_id,
                ];
            });

        return response()->json([
            'files' => $files,
        ]);
    }

    // GET /v1/inbox
    public function inbox(Request $request)
    {
        $user = $request->user();
        $conversations = Conversation::query()
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->with(['participants:id,uuid,full_name,email', 'messages' => function ($q) {
                $q->latest('created_at')->limit(1);
            }])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Conversation $conv) use ($user) {
                $last = $conv->messages->first();
                $summary = [
                    'conversationId' => $conv->id,
                    'conversationUuid' => $conv->uuid,
                    'type' => $conv->type,
                    'name' => $conv->name,
                    'updatedAt' => $conv->updated_at,
                    'lastMessage' => $last ? [
                        'id' => $last->id,
                        'uuid' => $last->uuid,
                        'type' => $last->message_type,
                        'content' => $last->content,
                        'fileUrl' => $last->file_url,
                        'createdAt' => $last->created_at,
                        'senderId' => $last->sender_id,
                    ] : null,
                ];

                if ($conv->type === 'direct') {
                    $other = $conv->participants->firstWhere('id', '!=', $user->id);
                    if ($other) {
                        $summary['otherParticipant'] = [
                            'id' => $other->id,
                            'uuid' => $other->uuid,
                            'full_name' => $other->full_name,
                            'email' => $other->email,
                        ];
                    }
                } else {
                    $summary['memberCount'] = $conv->participants->count();
                }

                return $summary;
            });

        return response()->json(['items' => $conversations]);
    }
}
