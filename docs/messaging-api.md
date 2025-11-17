# Messaging & Groups API (v1)

All endpoints require `Authorization: Bearer <token>` via Sanctum unless stated otherwise.

Base URL: `/v1`

Identifiers: All resources now include both numeric `id` and `uuid`. Clients should prefer `uuid` for external references. Endpoints accept either numeric IDs or UUIDs in path parameters where noted.

## Conversations

- POST `/v1/conversations/direct`
  - Description: Start or fetch a direct conversation with another user.
  - Body (JSON or form):
    - `participantId` (optional, integer) OR `participantUuid` (optional, string)
  - Response 201:
    - `conversationId` (number)
    - `conversationUuid` (string)

- GET `/v1/conversations`
  - Description: List conversations the auth user participates in.
  - Query:
    - `tab` (optional, `direct` | `group`)
  - Response 200:
    - `conversations` (array)
      - `id`, `uuid`, `type` (`direct`|`group`), `name`, `updatedAt`
      - `lastMessage` (nullable): `id`, `uuid`, `type`, `content`, `fileUrl`, `createdAt`
      - `participants` (array): items with `id`, `uuid`, `full_name`, `email`

- GET `/v1/conversations/{conversationId}/messages`
  - Description: Paginated messages in a conversation. `{conversationId}` accepts numeric ID or UUID.
  - Query:
    - `limit` (optional, default 50)
    - `before` (optional, numeric message id for backward pagination)
  - Response 200:
    - `conversation`: `id`, `uuid`, `type`, `name`, `updatedAt`, `participants[]`
    - `messages[]`: `id`, `uuid`, `conversationId`, `senderId`, `messageType`, `content`, `fileData{url,mimeType}`, `readAt`, `createdAt`, `deletedAt`

- GET `/v1/conversations/{conversationId}/files`
  - Description: List shared files in a conversation. `{conversationId}` accepts numeric ID or UUID.
  - Response 200:
    - `files[]`: `name`, `url`, `mimeType`, `sender` (user id)

## Messages

- POST `/v1/messages`
  - Description: Send a message to a conversation.
  - Body (multipart/form-data):
    - `conversationId` (optional, number) OR `conversationUuid` (optional, string)
    - `messageType` (required, `text` | `image` | `file` | `voicenote`)
    - `content` (optional, string; required for `text`)
    - `file` (optional; required for non-`text` types)
  - Response 201:
    - Message object: `id`, `uuid`, `conversationId`, `senderId`, `messageType`, `content`, `fileData{url,mimeType}`, `readAt`, `createdAt`

- DELETE `/v1/messages/{messageId}`
  - Description: Delete a message sent by the auth user. `{messageId}` accepts numeric ID or UUID.
  - Response 200:
    - `messageId` (string)
    - `messageUuid` (string)
    - `status` = `deleted`

- POST `/v1/messages/{messageId}/read`
  - Description: Mark message and prior others' messages as read. `{messageId}` accepts numeric ID or UUID.
  - Response 200:
    - `messageId` (string)
    - `messageUuid` (string)
    - `status` = `read`
    - `readAt` (timestamp)

## Groups

- POST `/v1/groups`
  - Description: Create a group conversation.
  - Body (JSON):
    - `name` (required, string)
    - `participants` (optional, array of user numeric ids)
    - `participantUuids` (optional, array of user uuids)
  - Response 201:
    - `groupId` (number)
    - `groupUuid` (string)
    - `name` (string)
    - `participants[]`: `id`, `uuid`, `full_name`, `email`

- POST `/v1/groups/{groupId}/participants`
  - Description: Add participants to a group. `{groupId}` accepts numeric ID or UUID.
  - Body (JSON):
    - `newParticipantIds` (optional, array of numeric ids)
    - `newParticipantUuids` (optional, array of uuids)
  - Response 200:
    - `groupId` (number)
    - `groupUuid` (string)
    - `updatedParticipants[]`: `id`, `uuid`, `full_name`, `email`

- DELETE `/v1/groups/{groupId}/participants/{userId}`
  - Description: Remove a participant. `{groupId}` and `{userId}` accept numeric IDs or UUIDs.
  - Response 200:
    - `groupId` (number)
    - `groupUuid` (string)
    - `removedUserId` (string)
    - `removedUserUuid` (string|null)

- POST `/v1/groups/{groupId}/leave`
  - Description: Leave a group. `{groupId}` accepts numeric ID or UUID.
  - Response 200:
    - `groupId` (number)
    - `groupUuid` (string)
    - `status` = `left`

- DELETE `/v1/groups/{groupId}`
  - Description: Delete a group (admin only). `{groupId}` accepts numeric ID or UUID.
  - Response 200:
    - `groupId` (string)
    - `groupUuid` (string)
    - `status` = `deleted`

- GET `/v1/groups/{groupId}/profile`
  - Description: Group profile with member list. `{groupId}` accepts numeric ID or UUID.
  - Response 200:
    - `name` (string)
    - `memberCount` (number)
    - `participants[]`: `id`, `uuid`, `full_name`, `email`
    - `admins[]`: `id`, `uuid`, `full_name`, `email`

## Inbox

- GET `/v1/inbox`
  - Description: Conversation summary list akin to WhatsApp. Includes last message and counterpart/group info.
  - Response 200:
    - `items[]`
      - `conversationId`, `conversationUuid`, `type`, `name`, `updatedAt`
      - `lastMessage` (nullable): `id`, `uuid`, `type`, `content`, `fileUrl`, `createdAt`, `senderId`
      - For direct: `otherParticipant` { `id`, `uuid`, `full_name`, `email` }
      - For group: `memberCount`

## Notes

- Private channels for WebSockets still use numeric `conversationId` in channel names. Clients should carry both `id` and `uuid` per conversation.
- For file uploads, send as `multipart/form-data`. The server stores to `/public/uploads/messages` and returns a relative URL.