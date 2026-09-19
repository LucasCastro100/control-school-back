<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Schedule::query();

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->query('class_id'));
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->query('room_id'));
        }

        if ($request->filled('school_id')) {
            $query->whereHas('schoolClass', fn ($q) => $q->where('school_id', $request->query('school_id')));
        }

        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->query('day_of_week'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'room_id' => 'nullable|exists:rooms,id',
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'subject' => 'required|string',
            'teacher' => 'required|string',
            'fortnight' => 'sometimes|nullable|integer|in:0,1,2',
        ]);

        return response()->json(Schedule::create($validated), 201);
    }

    public function show(Schedule $schedule): JsonResponse
    {
        return response()->json($schedule->load(['schoolClass', 'room']));
    }

    public function update(Request $request, Schedule $schedule): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => 'sometimes|exists:classes,id',
            'room_id' => 'sometimes|nullable|exists:rooms,id',
            'day_of_week' => 'sometimes|integer|between:0,6',
            'start_time' => 'sometimes|string',
            'end_time' => 'sometimes|string',
            'subject' => 'sometimes|string',
            'teacher' => 'sometimes|string',
            'fortnight' => 'sometimes|nullable|integer|in:0,1,2',
        ]);

        $schedule->update($validated);

        return response()->json($schedule);
    }

    public function destroy(Schedule $schedule): JsonResponse
    {
        $schedule->delete();

        return response()->json(null, 204);
    }
}
