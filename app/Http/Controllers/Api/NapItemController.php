<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NapItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NapItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = NapItem::query();

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->query('school_id'));
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->query('item_id'));
        }

        if ($request->filled('segment_name')) {
            $query->where('segment_name', $request->query('segment_name'));
        }

        if ($request->filled('year')) {
            $query->where('year', $request->query('year'));
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(static::rules());

        return response()->json(NapItem::create($validated), 201);
    }

    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate(static::rules());

        $napItem = NapItem::updateOrCreate(
            [
                'school_id' => $validated['school_id'],
                'segment_name' => $validated['segment_name'],
                'item_id' => $validated['item_id'],
                'year' => $validated['year'],
            ],
            $validated
        );

        return response()->json($napItem, 201);
    }

    public function show(NapItem $napItem): JsonResponse
    {
        return response()->json($napItem->load(['school', 'item']));
    }

    public function update(Request $request, NapItem $napItem): JsonResponse
    {
        $validated = $request->validate(static::rules());

        $napItem->update($validated);

        return response()->json($napItem);
    }

    public function destroy(NapItem $napItem): JsonResponse
    {
        $napItem->delete();

        return response()->json(null, 204);
    }

    public function destroyBySchool(string $schoolId): JsonResponse
    {
        NapItem::where('school_id', $schoolId)->delete();

        return response()->json(null, 204);
    }

    public function destroyBySchoolAndYear(string $schoolId, string $year): JsonResponse
    {
        NapItem::where('school_id', $schoolId)->where('year', $year)->delete();

        return response()->json(null, 204);
    }

    protected static function rules(): array
    {
        return [
            'school_id' => 'required|exists:schools,id',
            'segment_name' => 'required|string',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'sometimes|integer|min:0',
            'year' => 'required|string',
        ];
    }
}
