<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $milestones = Milestone::where('user_id', $request->user()->id)
            ->orderBy('due_date')
            ->get();

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
        ]);

        $milestone = Milestone::create([
            'user_id'  => $request->user()->id,
            ...$data,
        ]);

        return response()->json(['message' => 'Milestone dibuat.', 'data' => $milestone], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $milestone = Milestone::where('user_id', $request->user()->id)->findOrFail($id);

        return response()->json(['data' => $milestone]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $milestone = Milestone::where('user_id', $request->user()->id)->findOrFail($id);

        $data = $request->validate([
            'title'    => 'sometimes|string|max:255',
            'category' => 'sometimes|nullable|string|max:50',
            'due_date' => 'sometimes|date',
            'progress' => 'sometimes|integer|min:0|max:100',
            'notes'    => 'sometimes|nullable|string',
            'color'    => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $milestone->update($data);

        return response()->json(['message' => 'Milestone diperbarui.', 'data' => $milestone]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $milestone = Milestone::where('user_id', $request->user()->id)->findOrFail($id);
        $milestone->delete();

        return response()->json(['message' => 'Milestone dihapus.']);
    }

    // PATCH /api/milestones/{id}/progress
    public function updateProgress(Request $request, int $id): JsonResponse
    {
        $milestone = Milestone::where('user_id', $request->user()->id)->findOrFail($id);

        $data = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $milestone->update(['progress' => $data['progress']]);

        return response()->json([
            'message'  => 'Progress diperbarui.',
            'progress' => $milestone->progress,
        ]);
    }
}
