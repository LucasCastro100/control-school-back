<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\SchoolSegment;
use App\Support\Segments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolSegmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SchoolSegment::query();

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->query('school_id'));
        }

        if ($request->filled('year')) {
            $query->where('year', $request->query('year'));
        }

        return response()->json($query->orderBy('segment_name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'segment_name' => 'required|string|max:60',
            'year' => 'required|string',
            'material_type' => 'sometimes|nullable|in:jornada_z,epc,',
            'schedule_type' => 'sometimes|in:semanal,quinzenal,',
            'years' => 'sometimes|array',
            'years.*' => 'string',
        ]);

        $years = array_key_exists('years', $validated) ? $validated['years'] : null;
        unset($validated['years']);

        $segment = DB::transaction(function () use ($validated, $years) {
            $segment = SchoolSegment::updateOrCreate(
                [
                    'school_id' => $validated['school_id'],
                    'segment_name' => $validated['segment_name'],
                    'year' => $validated['year'],
                ],
                $validated
            );

            if (is_array($years)) {
                $this->syncClasses($segment, $years);
            }

            return $segment;
        });

        return response()->json($segment, 201);
    }

    /**
     * Creates one class per selected year/série and removes the previously
     * auto-created classes of this segment whose year was unselected.
     */
    private function syncClasses(SchoolSegment $segment, array $selectedYears): void
    {
        $validYears = Segments::yearsForLabel($segment->segment_name);

        if (empty($validYears)) {
            return;
        }

        $selected = array_values(array_intersect($validYears, $selectedYears));

        foreach ($selected as $yearName) {
            SchoolClass::firstOrCreate([
                'school_id' => $segment->school_id,
                'nap' => $segment->segment_name,
                'name' => $yearName,
                'year' => $segment->year,
            ]);
        }

        SchoolClass::where('school_id', $segment->school_id)
            ->where('year', $segment->year)
            ->where('nap', $segment->segment_name)
            ->whereIn('name', $validYears)
            ->whereNotIn('name', $selected)
            ->delete();
    }

    public function destroy(SchoolSegment $schoolSegment): JsonResponse
    {
        $schoolSegment->delete();

        return response()->json(null, 204);
    }
}