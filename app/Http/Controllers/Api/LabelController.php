<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Label;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $labels = Label::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $labels]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:50',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        // Cek duplikat per user
        $exists = Label::where('user_id', $request->user()->id)
            ->where('name', $data['name'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Label dengan nama ini sudah ada.'], 422);
        }

        $label = Label::create([
            'user_id' => $request->user()->id,
            'name'    => $data['name'],
            'color'   => $data['color'] ?? '#1D9E75',
        ]);

        return response()->json(['message' => 'Label dibuat.', 'data' => $label], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $label = Label::where('user_id', $request->user()->id)->findOrFail($id);

        $data = $request->validate([
            'name'  => 'sometimes|string|max:50',
            'color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $label->update($data);

        return response()->json(['message' => 'Label diperbarui.', 'data' => $label]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $label = Label::where('user_id', $request->user()->id)->findOrFail($id);
        $label->delete();

        return response()->json(['message' => 'Label dihapus.']);
    }
}
