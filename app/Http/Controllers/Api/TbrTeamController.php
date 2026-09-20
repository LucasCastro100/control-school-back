<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\TbrTeam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TbrTeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TbrTeam::query();

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->query('school_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'category_id' => 'required|exists:tbr_categories,id',
            'name' => 'required|string|max:255',
        ]);

        return response()->json(TbrTeam::create($validated), 201);
    }

    public function replaceForSchool(Request $request, string $schoolId): JsonResponse
    {
        $validated = $request->validate([
            'teams' => 'array',
            'teams.*.category_id' => 'required|exists:tbr_categories,id',
            'teams.*.name' => 'required|string|max:255',
        ]);

        if (! TbrTeam::where('school_id', $schoolId)->exists() && ! School::where('id', $schoolId)->exists()) {
            return response()->json(['message' => 'Escola não encontrada.'], 404);
        }

        TbrTeam::where('school_id', $schoolId)->delete();

        TbrTeam::insert(array_map(
            fn (array $team) => [
                'id' => (string) Str::uuid(),
                'school_id' => $schoolId,
                'category_id' => $team['category_id'],
                'name' => $team['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $validated['teams']
        ));

        return response()->json(TbrTeam::where('school_id', $schoolId)->get());
    }

    public function show(TbrTeam $tbrTeam): JsonResponse
    {
        return response()->json($tbrTeam->load(['school', 'category']));
    }

    public function update(Request $request, TbrTeam $tbrTeam): JsonResponse
    {
        $validated = $request->validate([
            'school_id' => 'sometimes|exists:schools,id',
            'category_id' => 'sometimes|exists:tbr_categories,id',
            'name' => 'sometimes|string|max:255',
        ]);

        $tbrTeam->update($validated);

        return response()->json($tbrTeam);
    }

    public function destroy(TbrTeam $tbrTeam): JsonResponse
    {
        $tbrTeam->delete();

        return response()->json(null, 204);
    }

    public function destroyBySchool(string $schoolId): JsonResponse
    {
        TbrTeam::where('school_id', $schoolId)->delete();

        return response()->json(null, 204);
    }
}
