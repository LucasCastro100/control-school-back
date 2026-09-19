<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SegmentConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SegmentConfigController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SegmentConfig::query();

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->query('school_id'));
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
            'segment_name' => 'required|string',
            'tapetes' => 'sometimes|integer|min:0',
            'kits' => 'sometimes|integer|min:0',
            'year' => 'required|string',
        ]);

        return response()->json(SegmentConfig::create($validated), 201);
    }

    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'segment_name' => 'required|string',
            'tapetes' => 'sometimes|integer|min:0',
            'kits' => 'sometimes|integer|min:0',
            'year' => 'required|string',
        ]);

        $config = SegmentConfig::updateOrCreate(
            [
                'school_id' => $validated['school_id'],
                'segment_name' => $validated['segment_name'],
                'year' => $validated['year'],
            ],
            $validated
        );

        return response()->json($config, 201);
    }

    public function show(SegmentConfig $segmentConfig): JsonResponse
    {
        return response()->json($segmentConfig->load('school'));
    }

    public function update(Request $request, SegmentConfig $segmentConfig): JsonResponse
    {
        $validated = $request->validate([
            'school_id' => 'sometimes|exists:schools,id',
            'segment_name' => 'sometimes|string',
            'tapetes' => 'sometimes|integer|min:0',
            'kits' => 'sometimes|integer|min:0',
            'year' => 'sometimes|string',
        ]);

        $segmentConfig->update($validated);

        return response()->json($segmentConfig);
    }

    public function destroy(SegmentConfig $segmentConfig): JsonResponse
    {
        $segmentConfig->delete();

        return response()->json(null, 204);
    }

    public function destroyBySchool(string $schoolId): JsonResponse
    {
        SegmentConfig::where('school_id', $schoolId)->delete();

        return response()->json(null, 204);
    }
}
