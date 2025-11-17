<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Events\MessageSent;
use App\Events\MessageDeleted;
use App\Events\MessageRead;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MessagesController extends Controller
{
    // POST /v1/messages
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'content' => ['nullable', 'string'],
            'conversationId' => ['nullable', 'integer', 'exists:conversations,id'],
            'conversationUuid' => ['nullable', 'string', 'exists:conversations,uuid'],
            'messageType' => ['required', 'in:text,image,file,voicenote'],
            'file' => ['nullable'],
        ]);

        if (empty($data['conversationId']) && empty($data['conversationUuid'])) {
            return response()->json(['message' => 'conversationId or conversationUuid is required'], 422);
        }

        $conversation = Conversation::when(!empty($data['conversationId']), function ($q) use ($data) {
                $q->where('id', $data['conversationId']);
            })
            ->when(!empty($data['conversationUuid']), function ($q) use ($data) {
                $q->orWhere('uuid', $data['conversationUuid']);
            })
            ->firstOrFail();

        // Authorization: must be a participant
        if (!$conversation->participants()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Handle file upload to public folder and capture URL/MIME
        $storedFileUrl = null;
        $storedFileMime = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $uploadDir = public_path('uploads/messages');
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $ext = $file->getClientOriginalExtension();
            $baseName = 'msg-' . $data['conversationId'] . '-' . time();
            $fileName = $baseName . ($ext ? '.' . $ext : '');
            $file->move($uploadDir, $fileName);
            $storedFileUrl = '/uploads/messages/' . $fileName;
            $storedFileMime = $file->getClientMimeType();
        } elseif ($data['messageType'] !== 'text') {
            return response()->json(['message' => 'file is required for non-text messages'], 422);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id, // ignore senderId from body, use auth user
            'message_type' => $data['messageType'],
            'content' => $data['content'] ?? null,
            'file_url' => $storedFileUrl,
            'file_mime_type' => $storedFileMime,
        ]);

        // Touch conversation updated_at
        $conversation->touch();

        // Broadcast new message to conversation subscribers
        broadcast(new MessageSent($conversation->id, (string)$conversation->uuid, $this->formatMessage($message)))->toOthers();
    
        return response()->json($this->formatMessage($message), 201);
    }

    // DELETE /v1/messages/{messageId}
    public function destroy(Request $request)
    {
        $user = $request->user();
      
        $data = $request->validate([
            'messageUuid' => ['required', 'string', 'exists:messages,uuid'],
        ]);
        $message = Message::where('uuid', $data['messageUuid'])->firstOrFail();
        
        $conversation = $message->conversation;
        if (!$conversation->participants()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($message->sender_id !== $user->id) {
            return response()->json(['message' => 'Only sender can delete this message'], 403);
        }

        $message->delete();

        // Broadcast deletion
        broadcast(new MessageDeleted($conversation->id, (string)$conversation->uuid, (string)$message->uuid))->toOthers();

        return response()->json([
            'messageId' => (string)$message->id,
            'messageUuid' => (string)$message->uuid,
            'status' => 'deleted',
        ]);
    }

    // POST /v1/messages/{messageId}/read
    public function markRead( Request $request)
    {
        $data = $request->validate([
            'messageUuid' => ['required', 'string', 'exists:messages,uuid'],
        ]);
        $user = $request->user();
        $message = Message::where('uuid', $data['messageUuid'])->firstOrFail();
        $conversation = $message->conversation;

        if (!$conversation->participants()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $now = now();
        // Mark target message and all preceding messages (from other users) as read
        if ($message->sender_id !== $user->id) {

            Message::where('conversation_id', $conversation->id)
                ->where('id', '<=', $message->id)
                ->whereNull('read_at')
                ->where('sender_id', '!=', $user->id)
                ->update(['read_at' => $now]);

            // Broadcast read receipt
            broadcast(new MessageRead($conversation->id, (string)$conversation->uuid, (string)$message->uuid, (string)$user->uuid, $now->toISOString()))->toOthers();
        }

        return response()->json([
            'messageId' => (string)$message->id,
            'messageUuid' => (string)$message->uuid,
            'status' => 'read',
            'readAt' => $now,
        ]);
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
