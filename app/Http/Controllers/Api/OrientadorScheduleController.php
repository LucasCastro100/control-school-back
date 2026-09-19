<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrientadorSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrientadorScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = OrientadorSchedule::query();

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->query('school_id'));
        }

        if ($request->filled('orientador_id')) {
            $query->where('orientador_id', $request->query('orientador_id'));
        }

        if ($request->filled('year')) {
            $query->where('year', $request->query('year'));
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'orientador_id' => 'required|exists:users,id',
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'activity' => 'required|string',
            'year' => 'required|string',
        ]);

        return response()->json(OrientadorSchedule::create($validated), 201);
    }

    public function show(OrientadorSchedule $orientadorSchedule): JsonResponse
    {
        return response()->json($orientadorSchedule->load(['school', 'orientador']));
    }

    public function update(Request $request, OrientadorSchedule $orientadorSchedule): JsonResponse
    {
        $validated = $request->validate([
            'school_id' => 'sometimes|exists:schools,id',
            'orientador_id' => 'sometimes|exists:users,id',
            'day_of_week' => 'sometimes|integer|between:0,6',
            'start_time' => 'sometimes|string',
            'end_time' => 'sometimes|string',
            'activity' => 'sometimes|string',
            'year' => 'sometimes|string',
        ]);

        $orientadorSchedule->update($validated);

        return response()->json($orientadorSchedule);
    }

    public function destroy(OrientadorSchedule $orientadorSchedule): JsonResponse
    {
        $orientadorSchedule->delete();

        return response()->json(null, 204);
    }

    public function destroyBySchool(string $schoolId): JsonResponse
    {
        OrientadorSchedule::where('school_id', $schoolId)->delete();

        return response()->json(null, 204);
    }
}
