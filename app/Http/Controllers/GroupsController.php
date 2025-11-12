<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;

class GroupsController extends Controller
{
    // POST /v1/groups
    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'participants' => ['required', 'array'],
            'participants.*' => ['integer', 'exists:users,id'],
        ]);

        $conversation = Conversation::create([
            'type' => 'group',
            'name' => $data['name'],
            'creator_id' => $user->id,
        ]);

        // Attach creator as admin
        $conversation->participants()->attach($user->id, ['is_admin' => true]);

        // Attach other participants
        $ids = collect($data['participants'])
            ->filter(fn ($id) => $id !== $user->id)
            ->unique()
            ->values()
            ->all();
        if (!empty($ids)) {
            $conversation->participants()->attach($ids, ['is_admin' => false]);
        }

        return response()->json([
            'groupId' => $conversation->id,
            'name' => $conversation->name,
            'participants' => $conversation->participants()->get(['users.id', 'users.full_name', 'users.email'])->map(function ($p) {
                return [
                    'id' => $p->id,
                    'full_name' => $p->full_name,
                    'email' => $p->email,
                ];
            }),
        ], 201);
    }

    // POST /v1/groups/{groupId}/participants
    public function addParticipants($groupId, Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'newParticipantIds' => ['required', 'array'],
            'newParticipantIds.*' => ['integer', 'exists:users,id'],
        ]);

        $group = Conversation::findOrFail($groupId);
        if ($group->type !== 'group') {
            return response()->json(['message' => 'Not a group conversation'], 400);
        }

        // Only admin can add
        $isAdmin = $group->participants()->where('users.id', $user->id)->wherePivot('is_admin', true)->exists();
        if (!$isAdmin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ids = collect($data['newParticipantIds'])->unique()->values()->all();
        foreach ($ids as $id) {
            if (!$group->participants()->where('users.id', $id)->exists()) {
                $group->participants()->attach($id, ['is_admin' => false]);
            }
        }

        $updated = $group->participants()->get(['users.id', 'users.full_name', 'users.email']);
        return response()->json([
            'groupId' => $group->id,
            'updatedParticipants' => $updated->map(function ($p) {
                return [
                    'id' => $p->id,
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
        $group = Conversation::findOrFail($groupId);
        if ($group->type !== 'group') {
            return response()->json(['message' => 'Not a group conversation'], 400);
        }

        $isAdmin = $group->participants()->where('users.id', $authUser->id)->wherePivot('is_admin', true)->exists();
        if (!$isAdmin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $group->participants()->detach($userId);

        return response()->json([
            'groupId' => $group->id,
            'removedUserId' => (string)$userId,
        ]);
    }

    // POST /v1/groups/{groupId}/leave
    public function leave($groupId, Request $request)
    {
        $user = $request->user();
        $group = Conversation::findOrFail($groupId);
        if ($group->type !== 'group') {
            return response()->json(['message' => 'Not a group conversation'], 400);
        }

        $group->participants()->detach($user->id);

        return response()->json([
            'groupId' => $group->id,
            'status' => 'left',
        ]);
    }

    // DELETE /v1/groups/{groupId}
    public function destroy($groupId, Request $request)
    {
        $user = $request->user();
        $group = Conversation::findOrFail($groupId);
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
            'groupId' => (string)$groupId,
            'status' => 'deleted',
        ]);
    }

    // GET /v1/groups/{groupId}/profile
    public function profile($groupId, Request $request)
    {
        $user = $request->user();
        $group = Conversation::with(['participants:id,full_name,email'])->findOrFail($groupId);
        if ($group->type !== 'group') {
            return response()->json(['message' => 'Not a group conversation'], 400);
        }

        // if (!$group->participants()->where('users.id', $user->id)->exists()) {
        //     return response()->json(['message' => 'Forbidden'], 403);
        // }

        $participants = $group->participants;
        $admins = $group->participants()->wherePivot('is_admin', true)->get(['users.id', 'users.full_name', 'users.email']);

        return response()->json([
            'name' => $group->name,
            'memberCount' => $participants->count(),
            'participants' => $participants->map(function ($p) {
                return [
                    'id' => $p->id,
                    'full_name' => $p->full_name,
                    'email' => $p->email,
                ];
            }),
            'admins' => $admins->map(function ($p) {
                return [
                    'id' => $p->id,
                    'full_name' => $p->full_name,
                    'email' => $p->email,
                ];
            }),
        ]);
    }
}