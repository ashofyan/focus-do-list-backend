<?php

namespace App\Services;

use App\Models\EncryptedNote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EncryptedNoteService
{
    public function paginateForUser(int $userId, array $filters): LengthAwarePaginator
    {
        $query = EncryptedNote::query()
            ->where('user_id', $userId)
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at');

        $this->applyFilters($query, $filters);

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function create(int $userId, array $data): EncryptedNote
    {
        return EncryptedNote::create([
            'user_id' => $userId,
            'encrypted_title' => $data['encrypted_title'] ?? null,
            'encrypted_content' => $data['encrypted_content'],
            'note_iv' => $data['note_iv'],
            'note_tag' => $data['note_tag'] ?? null,
            'encryption_version' => $data['encryption_version'],
            'is_archived' => $data['is_archived'] ?? false,
            'is_pinned' => $data['is_pinned'] ?? false,
            'last_synced_at' => $data['last_synced_at'] ?? null,
        ]);
    }

    public function update(EncryptedNote $note, array $data): EncryptedNote
    {
        $note->update($data);

        return $note->fresh();
    }

    public function softDelete(EncryptedNote $note): void
    {
        $note->forceFill(['is_deleted' => true])->save();
        $note->delete();
    }

    public function restore(EncryptedNote $note): EncryptedNote
    {
        $note->restore();
        $note->forceFill(['is_deleted' => false])->save();

        return $note->fresh();
    }

    public function archive(EncryptedNote $note, ?bool $state = null): EncryptedNote
    {
        $note->forceFill(['is_archived' => $state ?? ! $note->is_archived])->save();

        return $note->fresh();
    }

    public function pin(EncryptedNote $note, ?bool $state = null): EncryptedNote
    {
        $note->forceFill(['is_pinned' => $state ?? ! $note->is_pinned])->save();

        return $note->fresh();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $query->when(! array_key_exists('deleted', $filters), fn (Builder $query) => $query->where('is_deleted', false));
        $query->when(array_key_exists('deleted', $filters), function (Builder $query) use ($filters) {
            $query->withTrashed()->where('is_deleted', (bool) $filters['deleted']);
        });
        $query->when(array_key_exists('archived', $filters), fn (Builder $query) => $query->where('is_archived', (bool) $filters['archived']));
        $query->when(array_key_exists('pinned', $filters), fn (Builder $query) => $query->where('is_pinned', (bool) $filters['pinned']));
        $query->when(isset($filters['encryption_version']), fn (Builder $query) => $query->where('encryption_version', $filters['encryption_version']));
        $query->when(isset($filters['updated_since']), fn (Builder $query) => $query->where('updated_at', '>', $filters['updated_since']));
        $query->when(isset($filters['synced_since']), fn (Builder $query) => $query->where('last_synced_at', '>', $filters['synced_since']));
        $query->when(isset($filters['note_tag']), fn (Builder $query) => $query->whereJsonContains('note_tag', $filters['note_tag']));
    }
}
