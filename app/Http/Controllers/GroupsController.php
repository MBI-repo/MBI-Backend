<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GroupsController extends Controller
{
    // POST /v1/groups
    public function store(Request $request)
    {
        $user = $request->user();
       
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'participants' => ['nullable', 'array'],
            'participants.*' => ['string', 'exists:users,uuid'],
        ]);

        $conversation = Conversation::create([
            'type' => 'group',
            'name' => $data['name'],
            'creator_id' => $user->id,
        ]);

        // Attach creator as admin (use numeric ID)
        $conversation->participants()->attach($user->id, ['is_admin' => true]);

        // Attach other participants: map UUIDs to numeric user IDs before attach
        $uuids = collect($data['participants'] ?? [])
            ->filter(fn ($uuid) => $uuid !== $user->uuid)
            ->unique()
            ->values()
            ->all();

        if (!empty($uuids)) {
            $ids = \App\Models\User::whereIn('uuid', $uuids)
                ->pluck('id')
                ->filter(fn ($id) => $id !== $user->id)
                ->values()
                ->all();
            if (!empty($ids)) {
                $conversation->participants()->attach($ids, ['is_admin' => false]);
            }
        }

        return response()->json([
            'groupId' => $conversation->id,
            'groupUuid' => $conversation->uuid,
            'name' => $conversation->name,
            'participants' => $conversation->participants()->get(['users.id', 'users.uuid', 'users.full_name', 'users.email'])->map(function ($p) {
                return [
                    'id' => $p->id,
                    'uuid' => $p->uuid,
                    'full_name' => $p->full_name,
                    'email' => $p->email,
                ];
            }),
        ], 201);
    }

    // POST /v1/groups/participants
    public function addParticipants(Request $request)
    {
       
        $user = $request->user();
        $data = $request->validate([
            'groupUuid' => ['required', 'string'],
            // Accept ONLY UUIDs in newParticipantIds per client request
            'newParticipantIds' => ['required', 'array'],
            'newParticipantIds.*' => ['string', 'exists:users,uuid'],
        ]);

       
        $group = Conversation::where('uuid', $data['groupUuid'])->firstOrFail();
      
        if ($group->type !== 'group') {
            return response()->json(['message' => 'Not a group conversation'], 400);
        }

        // Only admin can add
        $isAdmin = $group->participants()->where('users.id', $user->id)->wherePivot('is_admin', true)->exists();
        if (!$isAdmin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Resolve UUIDs to numeric user IDs
        $uuidList = collect($data['newParticipantIds'] ?? [])->unique()->values()->all();
        $ids = \App\Models\User::whereIn('uuid', $uuidList)->pluck('id')->all();

        // Remove current user, dedupe, and attach
        $ids = collect($ids)->filter(fn ($id) => $id !== $user->id)->unique()->values()->all();
        foreach ($ids as $id) {
            if (!$group->participants()->where('users.id', $id)->exists()) {
                $group->participants()->attach($id, ['is_admin' => false]);
            }
        }

        $updated = $group->participants()->get(['users.id', 'users.uuid', 'users.full_name', 'users.email']);
        return response()->json([
            'groupId' => $group->id,
            'groupUuid' => $group->uuid,
            'updatedParticipants' => $updated->map(function ($p) {
                return [
                    'id' => $p->id,
                    'uuid' => $p->uuid,
                    'full_name' => $p->full_name,
                    'email' => $p->email,
                ];
            }),
        ]);
    }

    // DELETE /v1/groups/{groupId}/participants/{userId}
    public function removeParticipant($groupId, $userId, Request $request)
    {
        $authUser = $request->user();
        $group = Conversation::where('id', $groupId)->orWhere('uuid', $groupId)->firstOrFail();
        if ($group->type !== 'group') {
            return response()->json(['message' => 'Not a group conversation'], 400);
        }

        $isAdmin = $group->participants()->where('users.id', $authUser->id)->wherePivot('is_admin', true)->exists();
        if (!$isAdmin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Support removing by numeric id or uuid
        $targetUser = \App\Models\User::where('id', $userId)->orWhere('uuid', $userId)->first();
        if ($targetUser) {
            $group->participants()->detach($targetUser->id);
        }

        return response()->json([
            'groupId' => $group->id,
            'groupUuid' => $group->uuid,
            'removedUserId' => $targetUser ? (string)$targetUser->id : (string)$userId,
            'removedUserUuid' => $targetUser ? (string)$targetUser->uuid : null,
        ]);
    }

    // POST /v1/groups/{groupId}/leave
    public function leave(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'groupUuid' => ['required', 'string'],
        ]);
        $group = Conversation::where('uuid', $data['groupUuid'])->firstOrFail();
        if ($group->type !== 'group') {
            return response()->json(['message' => 'Not a group conversation'], 400);
        }

        $group->participants()->detach($user->id);

        return response()->json([
            'groupId' => $group->id,
            'groupUuid' => $group->uuid,
            'status' => 'left',
        ]);
    }

    // DELETE /v1/groups/{groupId}
    public function destroy($groupId, Request $request)
    {
        $user = $request->user();
        $group = Conversation::where('id', $groupId)->orWhere('uuid', $groupId)->firstOrFail();
        if ($group->type !== 'group') {
            return response()->json(['message' => 'Not a group conversation'], 400);
        }

        $isAdmin = $group->participants()->where('users.id', $user->id)->wherePivot('is_admin', true)->exists();
        if (!$isAdmin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Detach participants and delete messages, then the group
        $group->participants()->detach();
        $group->messages()->delete();
        $group->delete();

        return response()->json([
            'groupId' => (string)$group->id,
            'groupUuid' => (string)$group->uuid,
            'status' => 'deleted',
        ]);
    }

    // GET /v1/groups/{groupId}/profile
    public function profile($groupId, Request $request)
    {
        $user = $request->user();
        $group = Conversation::with(['participants:id,uuid,full_name,email'])
            ->where('id', $groupId)
            ->orWhere('uuid', $groupId)
            ->firstOrFail();
        if ($group->type !== 'group') {
            return response()->json(['message' => 'Not a group conversation'], 400);
        }

        // if (!$group->participants()->where('users.id', $user->id)->exists()) {
        //     return response()->json(['message' => 'Forbidden'], 403);
        // }

        $participants = $group->participants;
        $admins = $group->participants()->wherePivot('is_admin', true)->get(['users.id', 'users.uuid', 'users.full_name', 'users.email']);

        return response()->json([
            'name' => $group->name,
            'memberCount' => $participants->count(),
            'participants' => $participants->map(function ($p) {
                return [
                    'id' => $p->id,
                    'uuid' => $p->uuid,
                    'full_name' => $p->full_name,
                    'email' => $p->email,
                ];
            }),
            'admins' => $admins->map(function ($p) {
                return [
                    'id' => $p->id,
                    'uuid' => $p->uuid,
                    'full_name' => $p->full_name,
                    'email' => $p->email,
                ];
            }),
        ]);
    }
}