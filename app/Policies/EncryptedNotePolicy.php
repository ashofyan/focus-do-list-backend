<?php

namespace App\Policies;

use App\Models\EncryptedNote;

class EncryptedNotePolicy
{
    public function viewAny($user): bool
    {
        return isset($user->id);
    }

    public function view($user, EncryptedNote $note): bool
    {
        return (int) $user->id === (int) $note->user_id;
    }

    public function create($user): bool
    {
        return isset($user->id);
    }

    public function update($user, EncryptedNote $note): bool
    {
        return $this->view($user, $note);
    }

    public function delete($user, EncryptedNote $note): bool
    {
        return $this->view($user, $note);
    }

    public function restore($user, EncryptedNote $note): bool
    {
        return $this->view($user, $note);
    }

    public function archive($user, EncryptedNote $note): bool
    {
        return $this->view($user, $note);
    }

    public function pin($user, EncryptedNote $note): bool
    {
        return $this->view($user, $note);
    }
}
