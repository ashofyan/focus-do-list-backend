<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MilestoneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $milestones = Milestone::with(['todos.group', 'todos.labels', 'todos.subTasks'])
            ->where('user_id', $request->user()->id)
            ->orderBy('due_date')
            ->get();

        $milestones->each(fn (Milestone $milestone) => $this->appendTaskProgressStats($milestone));

        return response()->json(['data' => $milestones]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'    => 'required|string|max:255',
            'category' => 'nullable|string|max:50',
            'due_date' => 'required|date',
            'progress' => 'nullable|integer|min:0|max:100',
            'notes'    => 'nullable|string',
            'color'    => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'task_ids' => 'nullable|array',
            'task_ids.*' => [
                'integer',
                Rule::exists('todos', 'id')->where('user_id', $request->user()->id),
            ],
        ]);

        $milestone = DB::transaction(function () use ($data, $request) {
            $taskIds = $data['task_ids'] ?? [];
            unset($data['task_ids']);

            $milestone = Milestone::create([
                'user_id'  => $request->user()->id,
                ...$data,
            ]);

            $this->syncTasks($milestone, $taskIds, $request->user()->id);
            $milestone->refreshProgressFromTasks();

            return $this->loadMilestoneDetail($milestone);
        });

        return response()->json(['message' => 'Milestone dibuat.', 'data' => $milestone], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $milestone = Milestone::where('user_id', $request->user()->id)->findOrFail($id);

        return response()->json(['data' => $this->loadMilestoneDetail($milestone)]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $milestone = Milestone::where('user_id', $request->user()->id)->findOrFail($id);

        $data = $request->validate([
            'title'    => 'sometimes|string|max:255',
            'category' => 'sometimes|nullable|string|max:50',
            'due_date' => 'sometimes|date',
            'progress' => 'sometimes|integer|min:0|max:100',
            'notes'    => 'sometimes|nullable|string',
            'color'    => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'task_ids' => 'sometimes|array',
            'task_ids.*' => [
                'integer',
                Rule::exists('todos', 'id')->where('user_id', $request->user()->id),
            ],
        ]);

        $milestone = DB::transaction(function () use ($milestone, $data, $request) {
            $hasTaskIds = array_key_exists('task_ids', $data);
            $taskIds = $data['task_ids'] ?? [];
            unset($data['task_ids']);

            $milestone->update($data);

            if ($hasTaskIds) {
                $this->syncTasks($milestone, $taskIds, $request->user()->id);
            }

            $milestone->refreshProgressFromTasks();

            return $this->loadMilestoneDetail($milestone);
        });

        return response()->json(['message' => 'Milestone diperbarui.', 'data' => $milestone]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $milestone = Milestone::where('user_id', $request->user()->id)->findOrFail($id);
        $milestone->delete();

        return response()->json(['message' => 'Milestone dihapus.']);
    }

    // PATCH /api/milestones/{id}/progress
    public function updateProgress(Request $request, string $id): JsonResponse
    {
        $milestone = Milestone::where('user_id', $request->user()->id)->findOrFail($id);

        if ($milestone->todos()->exists()) {
            $milestone->refreshProgressFromTasks();

            return response()->json([
                'message' => 'Progress milestone dihitung dari task.',
                'progress' => $milestone->fresh()->progress,
            ]);
        }

        $data = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $milestone->update(['progress' => $data['progress']]);

        return response()->json([
            'message'  => 'Progress diperbarui.',
            'progress' => $milestone->progress,
        ]);
    }

    private function syncTasks(Milestone $milestone, array $taskIds, int $userId): void
    {
        Todo::where('user_id', $userId)
            ->where('milestone_id', $milestone->id)
            ->whereNotIn('id', $taskIds)
            ->update(['milestone_id' => null]);

        if ($taskIds === []) {
            return;
        }

        Todo::where('user_id', $userId)
            ->whereIn('id', $taskIds)
            ->update(['milestone_id' => $milestone->id]);
    }

    private function loadMilestoneDetail(Milestone $milestone): Milestone
    {
        $milestone = $milestone->fresh(['todos.group', 'todos.labels', 'todos.subTasks']);

        return $this->appendTaskProgressStats($milestone);
    }

    private function appendTaskProgressStats(Milestone $milestone): Milestone
    {
        $milestone->setAttribute('task_progress', $milestone->taskProgressStats());

        return $milestone;
    }
}
