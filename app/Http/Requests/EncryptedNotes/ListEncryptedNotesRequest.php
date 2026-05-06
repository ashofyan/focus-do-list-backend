<?php

namespace App\Http\Requests\EncryptedNotes;

use Illuminate\Foundation\Http\FormRequest;

class ListEncryptedNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'archived' => 'sometimes|boolean',
            'deleted' => 'sometimes|boolean',
            'pinned' => 'sometimes|boolean',
            'encryption_version' => 'sometimes|integer|min:1|max:65535',
            'updated_since' => 'sometimes|date',
            'synced_since' => 'sometimes|date',
            'note_tag' => 'sometimes|array|max:50',
            'note_tag.*' => 'string|max:4096',
            'q' => 'prohibited',
            'search' => 'prohibited',
            'title' => 'prohibited',
            'encrypted_title' => 'prohibited',
            'encrypted_content' => 'prohibited',
        ];
    }
}
