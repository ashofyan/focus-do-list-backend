<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TodoController extends Controller
{
    // -------------------------------------------------------------------------
    // GET /api/todos
    // Query params: status, priority, group_id, milestone_id, date, search, page
    // -------------------------------------------------------------------------
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Todo::with(['group', 'milestone', 'labels', 'subTasks'])
            ->where('user_id', $user->id)
            ->orderBy('is_pinned', 'desc')
            ->orderBy('sort_order')
            ->orderBy('due_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->filled('milestone_id')) {
            $query->where('milestone_id', $request->milestone_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('due_date', $request->date);
        }

        if ($request->filled('search')) {
            $query->where('title', 'ilike', '%' . $request->search . '%');
        }

        $todos = $query->paginate(20);

        return response()->json([
            'data'  => $todos->items(),
            'meta'  => [
                'current_page' => $todos->currentPage(),
                'last_page'    => $todos->lastPage(),
                'total'        => $todos->total(),
                'per_page'     => $todos->perPage(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/todos/today
    // -------------------------------------------------------------------------
    public function today(Request $request): JsonResponse
    {
        $todos = Todo::with(['group', 'milestone', 'labels', 'subTasks'])
            ->where('user_id', $request->user()->id)
            ->whereDate('due_date', today())
            ->orderBy('is_pinned', 'desc')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $todos]);
    }

    // -------------------------------------------------------------------------
    // GET /api/todos/pinned
    // -------------------------------------------------------------------------
    public function pinned(Request $request): JsonResponse
    {
        $todos = Todo::with(['group', 'milestone', 'labels', 'subTasks'])
            ->where('user_id', $request->user()->id)
            ->where('is_pinned', true)
            ->where('status', '!=', 'completed')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $todos]);
    }

    // -------------------------------------------------------------------------
    // POST /api/todos
    // -------------------------------------------------------------------------
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'due_date'       => 'nullable|date',
            'priority'       => 'nullable|in:low,medium,high',
            'group_id'       => 'nullable|exists:groups,id',
            'milestone_id'   => [
                'nullable',
                'integer',
                Rule::exists('milestones', 'id')->where('user_id', $request->user()->id),
            ],
            'is_pinned'      => 'nullable|boolean',
            'label_ids'      => 'nullable|array',
            'label_ids.*'    => 'exists:labels,id',
        ]);

        $todo = DB::transaction(function () use ($data, $request) {
            $todo = Todo::create([
                'user_id'      => $request->user()->id,
                'group_id'     => $data['group_id'] ?? null,
                'milestone_id' => $data['milestone_id'] ?? null,
                'title'        => $data['title'],
                'description'  => $data['description'] ?? null,
                'due_date'     => $data['due_date'] ?? null,
                'priority'     => $data['priority'] ?? 'medium',
                'is_pinned'    => $data['is_pinned'] ?? false,
            ]);

            if (! empty($data['label_ids'])) {
                $todo->labels()->sync($data['label_ids']);
            }

            $this->refreshMilestoneProgress($todo->milestone_id);

            return $todo->load(['group', 'milestone', 'labels', 'subTasks']);
        });

        return response()->json([
            'message' => 'Todo berhasil dibuat.',
            'data'    => $todo,
        ], 201);
    }

    // -------------------------------------------------------------------------
    // GET /api/todos/{id}
    // -------------------------------------------------------------------------
    public function show(Request $request, string $id): JsonResponse
    {
        $todo = Todo::with(['group', 'milestone', 'labels', 'subTasks'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json(['data' => $todo]);
    }

    // -------------------------------------------------------------------------
    // PUT /api/todos/{id}
    // -------------------------------------------------------------------------
    public function update(Request $request, string $id): JsonResponse
    {
        $todo = Todo::where('user_id', $request->user()->id)->findOrFail($id);

        $data = $request->validate([
            'title'        => 'sometimes|string|max:255',
            'description'  => 'sometimes|nullable|string',
            'due_date'     => 'sometimes|nullable|date',
            'priority'     => 'sometimes|in:low,medium,high',
            'status'       => 'sometimes|in:pending,in_progress,completed',
            'group_id'     => 'sometimes|nullable|exists:groups,id',
            'milestone_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('milestones', 'id')->where('user_id', $request->user()->id),
            ],
            'is_pinned'    => 'sometimes|boolean',
            'sort_order'   => 'sometimes|integer|min:0',
            'label_ids'    => 'sometimes|array',
            'label_ids.*'  => 'exists:labels,id',
        ]);

        DB::transaction(function () use ($todo, $data) {
            $oldMilestoneId = $todo->milestone_id;
            $hasLabelIds = array_key_exists('label_ids', $data);
            $labelIds = $data['label_ids'] ?? [];
            unset($data['label_ids']);

            $todo->update($data);

            if ($hasLabelIds) {
                $todo->labels()->sync($labelIds);
            }

            $todo->refresh();
            $this->refreshMilestoneProgress($oldMilestoneId);
            $this->refreshMilestoneProgress($todo->milestone_id);
        });

        return response()->json([
            'message' => 'Todo diperbarui.',
            'data'    => $todo->fresh(['group', 'milestone', 'labels', 'subTasks']),
        ]);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/todos/{id}
    // -------------------------------------------------------------------------
    public function destroy(Request $request, string $id): JsonResponse
    {
        $todo = Todo::where('user_id', $request->user()->id)->findOrFail($id);
        $milestoneId = $todo->milestone_id;
        $todo->delete();
        $this->refreshMilestoneProgress($milestoneId);

        return response()->json(['message' => 'Todo dihapus.']);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/todos/{id}/complete
    // -------------------------------------------------------------------------
    public function complete(Request $request, string $id): JsonResponse
    {
        $todo = Todo::where('user_id', $request->user()->id)->findOrFail($id);

        if ($todo->status === 'completed') {
            $todo->update(['status' => 'pending', 'completed_at' => null]);
            $message = 'Todo ditandai belum selesai.';
        } else {
            $todo->markComplete();
            $message = 'Todo ditandai selesai.';
        }

        $this->refreshMilestoneProgress($todo->milestone_id);

        return response()->json([
            'message' => $message,
            'data'    => $todo->fresh(['group', 'milestone', 'labels', 'subTasks']),
        ]);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/todos/{id}/pin
    // -------------------------------------------------------------------------
    public function togglePin(Request $request, string $id): JsonResponse
    {
        $todo = Todo::where('user_id', $request->user()->id)->findOrFail($id);
        $todo->update(['is_pinned' => ! $todo->is_pinned]);

        return response()->json([
            'message'   => $todo->is_pinned ? 'Todo dipin.' : 'Todo diunpin.',
            'is_pinned' => $todo->is_pinned,
        ]);
    }

    private function refreshMilestoneProgress(?int $milestoneId): void
    {
        if ($milestoneId === null) {
            return;
        }

        Milestone::find($milestoneId)?->refreshProgressFromTasks();
    }
}
