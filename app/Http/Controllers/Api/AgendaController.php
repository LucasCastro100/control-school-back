<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Agenda::query();

        if ($request->filled('orientador_id')) {
            $query->whereHas('orientadores', fn ($q) => $q->where('users.id', $request->query('orientador_id')));
        }

        if ($request->filled('date')) {
            $query->where('date', $request->query('date'));
        }

        return response()->json($query->with('orientadores')->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(static::rules());
        $orientadorIds = $validated['orientador_ids'] ?? [];
        unset($validated['orientador_ids']);

        $agenda = Agenda::create($validated);
        $agenda->orientadores()->sync($orientadorIds);

        return response()->json($agenda->load('orientadores'), 201);
    }

    public function show(Agenda $agenda): JsonResponse
    {
        return response()->json($agenda->load('orientadores'));
    }

    public function update(Request $request, Agenda $agenda): JsonResponse
    {
        $validated = $request->validate(static::rules());
        $orientadorIds = $validated['orientador_ids'] ?? [];
        unset($validated['orientador_ids']);

        $agenda->update($validated);
        $agenda->orientadores()->sync($orientadorIds);

        return response()->json($agenda->load('orientadores'));
    }

    public function destroy(Agenda $agenda): JsonResponse
    {
        $agenda->delete();

        return response()->json(null, 204);
    }

    protected static function rules(): array
    {
        return [
            'date' => 'required|string',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'activity' => 'required|string',
            'orientador_ids' => 'sometimes|array',
            'orientador_ids.*' => 'exists:users,id',
        ];
    }
}
