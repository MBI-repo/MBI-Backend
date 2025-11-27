<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Events\InboxUpdated;

class ConversationsController extends Controller
{
    // POST /v1/conversations/direct
    public function startDirect(Request $request)
    {
        try {
            $user = $request->user();
            $validator = Validator::make($request->all(), [
                'participantUuid' => ['nullable', 'string', 'exists:users,uuid'],
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }
            $data = $validator->validated();

            $participant = User::where('uuid', $data['participantUuid'])->firstOrFail();
            if ((int)$participant->id === (int)$user->id) {
                return response()->json(['success' => false, 'message' => 'Cannot start a direct conversation with yourself'], 422);
            }

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
                $conv->load(['participants:id,uuid,full_name,email']);
                $existing = $conv;

                // Broadcast inbox item to both participants so the new conversation appears in inbox
                foreach ($conv->participants as $p) {
                    $other = $conv->participants->firstWhere('id', '!=', $p->id);
                    $item = [
                        'conversationId' => $conv->id,
                        'conversationUuid' => $conv->uuid,
                        'type' => $conv->type,
                        'name' => $conv->name,
                        'updatedAt' => $conv->updated_at,
                        'lastMessage' => null,
                        'otherParticipant' => $other ? [
                            'id' => $other->id,
                            'uuid' => $other->uuid,
                            'full_name' => $other->full_name,
                            'email' => $other->email,
                        ] : null,
                    ];
                    broadcast(new InboxUpdated($p->id, (string)$p->uuid, $item))->toOthers();
                }
            }

            return response()->json([
                'conversationId' => $existing->id,
                'conversationUuid' => $existing->uuid,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    // GET /v1/conversations
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $tab = $request->query('tab');

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
                        'fileUrl' => $last->file_url ? 'https://api.mybridgeinternational.org/mybridge-backend-files/public' . $last->file_url : null,
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
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // GET /v1/conversations/{conversationId}/messages
    public function messages(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'conversationUuid' => ['required', 'string', 'exists:conversations,uuid'],
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }
            $data = $validator->validated();

            $user = $request->user();
            $limit = (int)($request->query('limit', 50));
            $beforeId = $request->query('before');

            $conversation = Conversation::with(['participants:id,full_name,email,uuid'])
                ->where('uuid', $data['conversationUuid'])
                ->firstOrFail();

            if (!$conversation->participants()->where('users.id', $user->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
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
                        'url' => 'https://api.mybridgeinternational.org/mybridge-backend-files/public'.$m->file_url,
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
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // GET /v1/conversations/{conversationId}/files
    public function files(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'conversationUuid' => ['required', 'string', 'exists:conversations,uuid'],
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }
            $data = $validator->validated();

            $user = $request->user();
            $conversation = Conversation::where('uuid', $data['conversationUuid'])->firstOrFail();
            if (!$conversation->participants()->where('users.id', $user->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
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
                        'url' => 'https://api.mybridgeinternational.org/mybridge-backend-files/public'.$m->file_url,
                        'mimeType' => $m->file_mime_type,
                        'sender' => $m->sender_id,
                    ];
                });

            return response()->json([
                'files' => $files,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // GET /v1/inbox
    public function inbox(Request $request)
    {
        try {
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
                            'fileUrl' => $last->file_url ? 'https://api.mybridgeinternational.org/mybridge-backend-files/public' . $last->file_url : null,
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
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
