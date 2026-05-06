<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EncryptedNotes\ListEncryptedNotesRequest;
use App\Http\Requests\EncryptedNotes\StoreEncryptedNoteRequest;
use App\Http\Requests\EncryptedNotes\UpdateEncryptedNoteRequest;
use App\Models\EncryptedNote;
use App\Services\EncryptedNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EncryptedNoteController extends Controller
{
    public function __construct(private readonly EncryptedNoteService $notes)
    {
    }

    public function index(ListEncryptedNotesRequest $request): JsonResponse
    {
        Gate::forUser($request->user())->authorize('viewAny', EncryptedNote::class);

        $notes = $this->notes->paginateForUser((int) $request->user()->id, $request->validated());

        return $this->success('Encrypted notes retrieved.', [
            'items' => $notes->items(),
            'meta' => [
                'current_page' => $notes->currentPage(),
                'last_page' => $notes->lastPage(),
                'total' => $notes->total(),
                'per_page' => $notes->perPage(),
            ],
        ]);
    }

    public function store(StoreEncryptedNoteRequest $request): JsonResponse
    {
        Gate::forUser($request->user())->authorize('create', EncryptedNote::class);

        $note = $this->notes->create((int) $request->user()->id, $request->validated());

        return $this->success('Encrypted note created.', $note, 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $note = $this->findOwnedNote($id, withTrashed: true);
        Gate::forUser($request->user())->authorize('view', $note);

        return $this->success('Encrypted note retrieved.', $note);
    }

    public function update(UpdateEncryptedNoteRequest $request, string $id): JsonResponse
    {
        $note = $this->findOwnedNote($id);
        Gate::forUser($request->user())->authorize('update', $note);

        $note = $this->notes->update($note, $request->validated());

        return $this->success('Encrypted note updated.', $note);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $note = $this->findOwnedNote($id);
        Gate::forUser($request->user())->authorize('delete', $note);

        $this->notes->softDelete($note);

        return $this->success('Encrypted note deleted.', null);
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $note = $this->findOwnedNote($id, withTrashed: true);
        Gate::forUser($request->user())->authorize('restore', $note);

        $note = $this->notes->restore($note);

        return $this->success('Encrypted note restored.', $note);
    }

    public function archive(Request $request, string $id): JsonResponse
    {
        $note = $this->findOwnedNote($id);
        Gate::forUser($request->user())->authorize('archive', $note);

        $data = $request->validate([
            'is_archived' => 'sometimes|boolean',
        ]);

        $note = $this->notes->archive($note, $data['is_archived'] ?? null);

        return $this->success('Encrypted note archive state updated.', $note);
    }

    public function pin(Request $request, string $id): JsonResponse
    {
        $note = $this->findOwnedNote($id, withTrashed: true);
        Gate::forUser($request->user())->authorize('pin', $note);

        $data = $request->validate([
            'is_pinned' => 'sometimes|boolean',
        ]);

        $note = $this->notes->pin($note, $data['is_pinned'] ?? null);

        return $this->success('Encrypted note pin state updated.', $note);
    }

    private function findOwnedNote(string $id, bool $withTrashed = false): EncryptedNote
    {
        $query = EncryptedNote::query()->whereKey($id);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->firstOrFail();
    }

    private function success(string $message, mixed $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
