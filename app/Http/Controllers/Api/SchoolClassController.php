<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SchoolClass::query();

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->query('school_id'));
        }

        if ($request->filled('year')) {
            $query->where('year', $request->query('year'));
        }

        if ($request->filled('nap')) {
            $query->where('nap', $request->query('nap'));
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'nap' => 'required|string',
            'name' => 'required|string|max:255',
            'year' => 'required|string',
        ]);

        return response()->json(SchoolClass::create($validated), 201);
    }

    public function show(SchoolClass $class): JsonResponse
    {
        return response()->json($class->load(['rooms', 'schedules']));
    }

    public function update(Request $request, SchoolClass $class): JsonResponse
    {
        $validated = $request->validate([
            'school_id' => 'sometimes|exists:schools,id',
            'nap' => 'sometimes|string',
            'name' => 'sometimes|string|max:255',
            'year' => 'sometimes|string',
        ]);

        $class->update($validated);

        return response()->json($class);
    }

    public function destroy(SchoolClass $class): JsonResponse
    {
        $class->delete();

        return response()->json(null, 204);
    }
}
