<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Events\MessageSent;
use App\Events\MessageDeleted;
use App\Events\MessageRead;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class MessagesController extends Controller
{
    // POST /v1/messages
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'content' => ['nullable', 'string'],
                'conversationId' => ['nullable', 'integer', 'exists:conversations,id'],
                'conversationUuid' => ['nullable', 'string', 'exists:conversations,uuid'],
                'messageType' => ['required', 'in:text,image,file,voicenote'],
                'file' => ['nullable'],
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }
            $data = $validator->validated();

            if (empty($data['conversationId']) && empty($data['conversationUuid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => ['conversation' => ['conversationId or conversationUuid is required']]
                ], 422);
            }

            $conversation = Conversation::when(!empty($data['conversationId']), function ($q) use ($data) {
                    $q->where('id', $data['conversationId']);
                })
                ->when(!empty($data['conversationUuid']), function ($q) use ($data) {
                    $q->orWhere('uuid', $data['conversationUuid']);
                })
                ->firstOrFail();

            if (!$conversation->participants()->where('users.id', $user->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $storedFileUrl = null;
            $storedFileMime = null;
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $uploadDir = public_path('uploads/messages');
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $ext = $file->getClientOriginalExtension();
                $baseName = 'msg-' . ($data['conversationId'] ?? 'uuid') . '-' . time();
                $fileName = $baseName . ($ext ? '.' . $ext : '');
                $file->move($uploadDir, $fileName);
                $storedFileUrl = '/uploads/messages/' . $fileName;
                $storedFileMime = $file->getClientMimeType();
            } elseif ($data['messageType'] !== 'text') {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => ['file' => ['file is required for non-text messages']]
                ], 422);
            }

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'message_type' => $data['messageType'],
                'content' => $data['content'] ?? null,
                'file_url' => $storedFileUrl,
                'file_mime_type' => $storedFileMime,
            ]);

            $conversation->touch();

            broadcast(new MessageSent($conversation->id, (string)$conversation->uuid, $this->formatMessage($message)))->toOthers();
            return response()->json($this->formatMessage($message), 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // DELETE /v1/messages/{messageId}
    public function destroy(Request $request)
    {
        try {
            $user = $request->user();
            $validator = Validator::make($request->all(), [
                'messageUuid' => ['required', 'string', 'exists:messages,uuid'],
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }
            $data = $validator->validated();

            $message = Message::where('uuid', $data['messageUuid'])->firstOrFail();
            $conversation = $message->conversation;
            if (!$conversation->participants()->where('users.id', $user->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }
            if ($message->sender_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Only sender can delete this message'], 403);
            }

            $message->delete();

            broadcast(new MessageDeleted($conversation->id, (string)$conversation->uuid, (string)$message->uuid))->toOthers();
            return response()->json([
                'messageId' => (string)$message->id,
                'messageUuid' => (string)$message->uuid,
                'status' => 'deleted',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // POST /v1/messages/{messageId}/read
    public function markRead( Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'messageUuid' => ['required', 'string', 'exists:messages,uuid'],
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
            $message = Message::where('uuid', $data['messageUuid'])->firstOrFail();
            $conversation = $message->conversation;

            if (!$conversation->participants()->where('users.id', $user->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }
            $now = now();
            if ($message->sender_id !== $user->id) {
                Message::where('conversation_id', $conversation->id)
                    ->where('id', '<=', $message->id)
                    ->whereNull('read_at')
                    ->where('sender_id', '!=', $user->id)
                    ->update(['read_at' => $now]);

                broadcast(new MessageRead($conversation->id, (string)$conversation->uuid, (string)$message->uuid, (string)$user->uuid, $now->toISOString()))->toOthers();
            }

            return response()->json([
                'messageId' => (string)$message->id,
                'messageUuid' => (string)$message->uuid,
                'status' => 'read',
                'readAt' => $now,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function formatMessage(Message $m): array
    {
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
        ];
    }
}
