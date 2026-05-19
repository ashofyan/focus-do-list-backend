<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DailyProgressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DailyProgress::with(['todo.group', 'todo.milestone', 'todo.labels', 'todo.subTasks'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('progress_date')
            ->orderByDesc('created_at');

        if ($request->filled('date')) {
            $query->whereDate('progress_date', $request->date);
        }

        if ($request->filled('todo_id')) {
            $query->where('todo_id', $request->todo_id);
        }

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('title', 'ilike', '%'.$request->search.'%')
                    ->orWhere('description', 'ilike', '%'.$request->search.'%');
            });
        }

        $progresses = $query->paginate(20);

        return response()->json([
            'data' => $progresses->items(),
            'meta' => [
                'current_page' => $progresses->currentPage(),
                'last_page' => $progresses->lastPage(),
                'total' => $progresses->total(),
                'per_page' => $progresses->perPage(),
            ],
        ]);
    }

    public function today(Request $request): JsonResponse
    {
        $progresses = DailyProgress::with(['todo.group', 'todo.milestone', 'todo.labels', 'todo.subTasks'])
            ->where('user_id', $request->user()->id)
            ->whereDate('progress_date', today())
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $progresses]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'progress_date' => 'nullable|date',
            'todo_id' => [
                'nullable',
                'integer',
                Rule::exists('todos', 'id')->where('user_id', $request->user()->id),
            ],
        ]);

        $progress = DailyProgress::create([
            'user_id' => $request->user()->id,
            'todo_id' => $data['todo_id'] ?? null,
            'progress_date' => $data['progress_date'] ?? today(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Daily progress dibuat.',
            'data' => $progress->load(['todo.group', 'todo.milestone', 'todo.labels', 'todo.subTasks']),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $progress = DailyProgress::with(['todo.group', 'todo.milestone', 'todo.labels', 'todo.subTasks'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json(['data' => $progress]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $progress = DailyProgress::where('user_id', $request->user()->id)->findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'progress_date' => 'sometimes|date',
            'todo_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('todos', 'id')->where('user_id', $request->user()->id),
            ],
        ]);

        $progress->update($data);

        return response()->json([
            'message' => 'Daily progress diperbarui.',
            'data' => $progress->fresh(['todo.group', 'todo.milestone', 'todo.labels', 'todo.subTasks']),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $progress = DailyProgress::where('user_id', $request->user()->id)->findOrFail($id);
        $progress->delete();

        return response()->json(['message' => 'Daily progress dihapus.']);
    }
}
