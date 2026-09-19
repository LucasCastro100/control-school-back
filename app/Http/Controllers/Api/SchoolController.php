<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = School::query();

        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        if ($request->filled('year')) {
            $query->whereHas('classes', fn ($q) => $q->where('year', $request->query('year')));
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(static::rules());

        return response()->json(School::create($validated), 201);
    }

    public function show(School $school): JsonResponse
    {
        return response()->json($school);
    }

    public function update(Request $request, School $school): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|nullable',
            'region' => 'sometimes|string|nullable',
            'state' => 'sometimes|string|nullable',
            'city' => 'sometimes|string|nullable',
            'color' => 'sometimes|string|nullable',
            'email' => 'sometimes|email|nullable|unique:schools,email,'.$school->id,
            'password' => 'sometimes|string|nullable',
            'schedule_type' => 'sometimes|in:semanal,quinzenal,',
            'active' => 'sometimes|boolean',
        ]);

        $school->update($validated);

        return response()->json($school);
    }

    public function destroy(School $school): JsonResponse
    {
        $school->delete();

        return response()->json(null, 204);
    }

    public function users(School $school): JsonResponse
    {
        return response()->json($school->users()->get());
    }

    protected static function rules(?string $id = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'sometimes|string|nullable',
            'region' => 'sometimes|string|nullable',
            'state' => 'sometimes|string|nullable',
            'city' => 'sometimes|string|nullable',
            'color' => 'sometimes|string|nullable',
            'email' => 'sometimes|email|nullable|unique:schools,email,'.$id,
            'password' => 'sometimes|string|nullable',
            'schedule_type' => 'sometimes|in:semanal,quinzenal,',
            'active' => 'sometimes|boolean',
        ];
    }
}
