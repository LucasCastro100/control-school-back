<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TbrCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TbrCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(TbrCategory::latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        return response()->json(TbrCategory::create($validated), 201);
    }

    public function show(TbrCategory $tbrCategory): JsonResponse
    {
        return response()->json($tbrCategory->load('teams'));
    }

    public function update(Request $request, TbrCategory $tbrCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
        ]);

        $tbrCategory->update($validated);

        return response()->json($tbrCategory);
    }

    public function destroy(TbrCategory $tbrCategory): JsonResponse
    {
        $tbrCategory->delete();

        return response()->json(null, 204);
    }
}
