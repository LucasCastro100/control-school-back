<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
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
        $validated = static::normalizeNullableStrings($validated);

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

        $validated = static::normalizeNullableStrings($validated);

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
        return response()->json($school->users()->with('roleModel')->get(['users.*', 'user_schools.nap']));
    }

    public function replaceUsers(Request $request, School $school): JsonResponse
    {
        $validated = $request->validate([
            'user_links' => 'required|array',
            'user_links.*.user_id' => 'required|exists:users,id',
            'user_links.*.nap' => 'nullable|string|max:20',
        ]);

        \App\Support\NapCapacity::assertFinalCountsWithinLimit($validated['user_links']);

        $pivot = collect($validated['user_links'])
            ->mapWithKeys(fn ($l) => [$l['user_id'] => ['nap' => $l['nap'] ?? null]])
            ->all();

        $school->users()->sync($pivot);

        return response()->json($school->users()->with('roleModel')->get(['users.*', 'user_schools.nap']));
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

    protected static function normalizeNullableStrings(array $data): array
    {
        foreach (['address', 'region', 'state', 'city'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                $data[$field] = '';
            }
        }

        return $data;
    }
}
