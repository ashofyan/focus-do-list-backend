<?php

namespace App\Http\Requests\EncryptedNotes;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncryptedNoteRequest extends FormRequest
{
    private const MAX_ENCRYPTED_TITLE_SIZE = 8192;
    private const MAX_ENCRYPTED_CONTENT_SIZE = 1048576;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'encrypted_title' => 'nullable|string|max:' . self::MAX_ENCRYPTED_TITLE_SIZE,
            'encrypted_content' => 'required|string|max:' . self::MAX_ENCRYPTED_CONTENT_SIZE,
            'note_iv' => 'required|string|max:512',
            'note_tag' => 'nullable|array|max:50',
            'note_tag.*' => 'string|max:4096',
            'encryption_version' => 'required|integer|min:1|max:65535',
            'is_archived' => 'sometimes|boolean',
            'is_pinned' => 'sometimes|boolean',
            'last_synced_at' => 'nullable|date',
        ];
    }
}
