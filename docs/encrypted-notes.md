# Encrypted Notes API

Encrypted notes use a zero-knowledge backend contract. The API stores encrypted payloads only. Encryption keys, user passwords, plaintext titles, plaintext content, and plaintext tags must stay in the client.

## Architecture Rules

- Encrypt and decrypt notes in the frontend/client only.
- Never send plaintext note fields to the backend.
- Never send the user's encryption password or derived master key to the backend.
- Treat `encrypted_title`, `encrypted_content`, `note_iv`, and `note_tag` as opaque ciphertext metadata.
- Backend search is limited to metadata filters such as `is_archived`, `is_deleted`, `is_pinned`, `updated_since`, `synced_since`, and `encryption_version`.
- Title and content are not searchable by the backend because both are encrypted.

## Encrypted Payload Example

```json
{
  "encrypted_title": "base64-ciphertext-title",
  "encrypted_content": "base64-ciphertext-content",
  "note_iv": "base64-random-iv-or-nonce",
  "note_tag": [
    "base64-encrypted-or-blind-index-tag"
  ],
  "encryption_version": 1,
  "is_archived": false,
  "is_pinned": false,
  "last_synced_at": "2026-05-06T04:30:00Z"
}
```

## Standard Response

```json
{
  "success": true,
  "message": "Encrypted note created.",
  "data": {
    "id": "018f8f5c-5c78-77d6-bc17-a8f3d99c3956",
    "user_id": 1,
    "encrypted_title": "base64-ciphertext-title",
    "encrypted_content": "base64-ciphertext-content",
    "note_iv": "base64-random-iv-or-nonce",
    "note_tag": ["base64-encrypted-or-blind-index-tag"],
    "encryption_version": 1,
    "is_archived": false,
    "is_deleted": false,
    "is_pinned": false,
    "last_synced_at": "2026-05-06T04:30:00.000000Z",
    "created_at": "2026-05-06T04:30:00.000000Z",
    "updated_at": "2026-05-06T04:30:00.000000Z"
  }
}
```

## Endpoints

- `GET /api/notes`
- `POST /api/notes`
- `GET /api/notes/{id}`
- `PUT /api/notes/{id}`
- `DELETE /api/notes/{id}`
- `POST /api/notes/{id}/restore`
- `POST /api/notes/{id}/archive`
- `POST /api/notes/{id}/pin`

## Sync Notes

Use `updated_at` for optimistic versioning and conflict detection. Multi-device clients should pull changes with `updated_since` or `synced_since`, then resolve conflicts client-side after decrypting local copies.

For future sharing, add a separate note-access table that stores recipient user IDs and encrypted per-recipient data keys. Do not store raw data keys on the backend.
