<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $groups = Group::where('user_id', $request->user()->id)
            ->withCount('todos')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $groups]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'color'      => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $group = Group::create([
            'user_id'    => $request->user()->id,
            'name'       => $data['name'],
            'color'      => $data['color'] ?? '#6C63FF',
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return response()->json(['message' => 'Group dibuat.', 'data' => $group], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $group = Group::where('user_id', $request->user()->id)
            ->withCount('todos')
            ->findOrFail($id);

        return response()->json(['data' => $group]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $group = Group::where('user_id', $request->user()->id)->findOrFail($id);

        $data = $request->validate([
            'name'       => 'sometimes|string|max:100',
            'color'      => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $group->update($data);

        return response()->json(['message' => 'Group diperbarui.', 'data' => $group]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $group = Group::where('user_id', $request->user()->id)->findOrFail($id);
        // Todos dalam group → group_id menjadi null (nullOnDelete di migration)
        $group->delete();

        return response()->json(['message' => 'Group dihapus.']);
    }
}
