<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }

        if ($request->filled('school_id')) {
            $query->whereHas('schools', fn (Builder $q) => $q->where('schools.id', $request->query('school_id')));
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(static::rules());

        return response()->json(User::create($validated), 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user->load('schools'));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate(static::updateRules($user->id));

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json($user);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(null, 204);
    }

    public function schools(User $user): JsonResponse
    {
        return response()->json($user->schools()->get());
    }

    public function replaceSchools(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'school_ids' => 'required|array',
            'school_ids.*' => 'exists:schools,id',
        ]);

        $user->schools()->sync($validated['school_ids']);

        return response()->json($user->schools()->get());
    }

    public function addSchool(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
        ]);

        $user->schools()->syncWithoutDetaching($validated['school_id']);

        return response()->json($user->schools()->get());
    }

    public function removeSchool(User $user, string $schoolId): JsonResponse
    {
        $user->schools()->detach($schoolId);

        return response()->json($user->schools()->get());
    }

    protected static function rules(?string $id = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id,
            'password' => 'sometimes|string|min:6',
            'role' => 'required|in:admin,orientador,professor,escola',
        ];
    }

    protected static function updateRules(?string $id = null): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$id,
            'password' => 'sometimes|string|min:6',
            'role' => 'sometimes|in:admin,orientador,professor,escola',
        ];
    }
}
