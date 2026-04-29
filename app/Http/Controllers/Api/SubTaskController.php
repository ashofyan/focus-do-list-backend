<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubTask;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubTaskController extends Controller
{
    // POST /api/todos/{id}/sub-tasks
    public function store(Request $request, int $id): JsonResponse
    {
        $todo = Todo::where('user_id', $request->user()->id)->findOrFail($id);

        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $subTask = $todo->subTasks()->create([
            'title'      => $data['title'],
            'sort_order' => $data['sort_order'] ?? $todo->subTasks()->count(),
        ]);

        return response()->json([
            'message' => 'Sub-task ditambahkan.',
            'data'    => $subTask,
        ], 201);
    }

    // PATCH /api/todos/{id}/sub-tasks/{sid} - toggle complete
    public function toggle(Request $request, int $id, int $sid): JsonResponse
    {
        $todo    = Todo::where('user_id', $request->user()->id)->findOrFail($id);
        $subTask = SubTask::where('todo_id', $todo->id)->findOrFail($sid);

        $subTask->update([
            'is_completed' => ! $subTask->is_completed,
            'completed_at' => ! $subTask->is_completed ? now() : null,
        ]);

        return response()->json([
            'message'      => $subTask->is_completed ? 'Sub-task selesai.' : 'Sub-task dibatalkan.',
            'is_completed' => $subTask->is_completed,
            'data'         => $subTask->fresh(),
        ]);
    }

    // PUT /api/todos/{id}/sub-tasks/{sid} - rename
    public function update(Request $request, int $id, int $sid): JsonResponse
    {
        $todo    = Todo::where('user_id', $request->user()->id)->findOrFail($id);
        $subTask = SubTask::where('todo_id', $todo->id)->findOrFail($sid);

        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $subTask->update($data);

        return response()->json(['message' => 'Sub-task diperbarui.', 'data' => $subTask]);
    }

    // DELETE /api/todos/{id}/sub-tasks/{sid}
    public function destroy(Request $request, int $id, int $sid): JsonResponse
    {
        $todo    = Todo::where('user_id', $request->user()->id)->findOrFail($id);
        $subTask = SubTask::where('todo_id', $todo->id)->findOrFail($sid);
        $subTask->delete();

        return response()->json(['message' => 'Sub-task dihapus.']);
    }
}
