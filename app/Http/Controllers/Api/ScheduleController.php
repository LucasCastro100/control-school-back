<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SchoolSegment;
use App\Support\Segments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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

        $this->applyBusinessRules($validated);

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

        $data = array_merge([
            'class_id' => $schedule->class_id,
            'room_id' => $schedule->room_id,
            'day_of_week' => $schedule->day_of_week,
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
            'fortnight' => $schedule->fortnight,
        ], $validated);

        $this->applyBusinessRules($data, $schedule->id);

        $schedule->update($validated);

        return response()->json($schedule);
    }

    public function destroy(Schedule $schedule): JsonResponse
    {
        $schedule->delete();

        return response()->json(null, 204);
    }

    private function applyBusinessRules(array $data, ?string $ignoreScheduleId = null): void
    {
        $class = SchoolClass::with('school')->find($data['class_id'] ?? null);

        if ($class === null) {
            throw ValidationException::withMessages(['class_id' => 'Turma não encontrada.']);
        }

        $segment = Segments::forClass($class);

        $start = $data['start_time'] ?? null;
        $end = $data['end_time'] ?? null;

        if ($start !== null && $end !== null) {
            $startTs = strtotime($start);
            $endTs = strtotime($end);

            if ($startTs === false || $endTs === false || $endTs <= $startTs) {
                throw ValidationException::withMessages([
                    'end_time' => 'O horário final deve ser depois do horário inicial.',
                ]);
            }

            $minutes = (int) round(($endTs - $startTs) / 60);

            $material = null;
            if ($segment !== null) {
                $config = SchoolSegment::where('school_id', $class->school_id)
                    ->where('segment_name', $segment)
                    ->where('year', $class->year)
                    ->first();
                $material = $config->material_type ?? null;
            }

            if ($material === 'epc' && $minutes > 50) {
                throw ValidationException::withMessages([
                    'end_time' => 'Segmento EPC: a aula deve ter no máximo 50 minutos.',
                ]);
            }

            if ($material === 'jornada_z' && ! in_array($minutes, [50, 100], true)) {
                throw ValidationException::withMessages([
                    'end_time' => 'Segmento Jornada Z: a aula deve ter 50 ou 100 minutos.',
                ]);
            }
        }

        if ($segment === null || $start === null || $end === null) {
            return;
        }

        $fortnight = ! empty($data['fortnight']) ? (int) $data['fortnight'] : 0;

        $conflict = Schedule::query()
            ->where('class_id', '!=', $class->id)
            ->where('day_of_week', $data['day_of_week'] ?? null)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->when($ignoreScheduleId !== null, fn ($q) => $q->where('id', '!=', $ignoreScheduleId))
            ->whereHas('schoolClass', fn ($q) => $q->where('school_id', $class->school_id))
            ->with('schoolClass')
            ->get()
            ->contains(function (Schedule $schedule) use ($segment, $fortnight) {
                if (Segments::forClass($schedule->schoolClass) !== $segment) {
                    return false;
                }

                $other = ! empty($schedule->fortnight) ? (int) $schedule->fortnight : 0;

                return $fortnight === 0 || $other === 0 || $fortnight === $other;
            });

        if ($conflict) {
            throw ValidationException::withMessages([
                'start_time' => 'Já existe aula de outra turma do mesmo segmento no mesmo dia e horário.',
            ]);
        }
    }
}
