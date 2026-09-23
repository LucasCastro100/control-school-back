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
        $query = User::query()->with(['roleModel', 'schools']);

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

        return response()->json(User::create($validated)->load(['roleModel', 'schools']), 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user->load(['roleModel', 'schools']));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate(static::updateRules($user->id));

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json($user->load(['roleModel', 'schools']));
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(null, 204);
    }

    public function schools(User $user): JsonResponse
    {
        return response()->json($user->schools()->get(['schools.*', 'user_schools.nap']));
    }

    public function mundoz(Request $request, User $user): JsonResponse
    {
        $current = $request->user();
        $isOwner = $current && $current->id === $user->id;
        $isAdmin = $current && $current->role === 'admin';

        if (! $isOwner && ! $isAdmin) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        return response()->json([
            'mundoz_user' => $user->mundoz_user,
            'mundoz_password' => $user->mundoz_password,
        ]);
    }

    public function replaceSchools(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'school_links' => 'required_without:school_ids|array',
            'school_links.*.school_id' => 'required|exists:schools,id',
            'school_links.*.nap' => 'nullable|string|max:20',
            'school_ids' => 'sometimes|array',
            'school_ids.*' => 'exists:schools,id',
        ]);

        $links = $validated['school_links'] ?? collect($validated['school_ids'])->map(fn ($id) => ['school_id' => $id, 'nap' => null])->all();
        $pivot = collect($links)->mapWithKeys(fn ($l) => [$l['school_id'] => ['nap' => $l['nap'] ?? null]])->all();

        $current = $user->schools()->get(['schools.id', 'user_schools.nap']);
        $currentNap = $current
            ->mapWithKeys(fn ($s) => [$s['id'] => $s->pivot?->nap])
            ->all();

        foreach ($links as $link) {
            $nap = $link['nap'] ?? null;
            if ($nap === null || $nap === '') {
                continue;
            }

            $alreadyOwns = ($currentNap[$link['school_id']] ?? null) === $nap;
            if (! $alreadyOwns) {
                \App\Support\NapCapacity::assertWithinLimit($link['school_id'], $nap);
            }
        }

        $user->schools()->sync($pivot);

        return response()->json($user->schools()->get(['schools.*', 'user_schools.nap']));
    }

    public function addSchool(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'nap' => 'nullable|string|max:20',
        ]);

        \App\Support\NapCapacity::assertWithinLimit(
            $validated['school_id'],
            $validated['nap'] ?? null,
            $user->id
        );

        $user->schools()->syncWithoutDetaching([$validated['school_id'] => ['nap' => $validated['nap'] ?? null]]);

        return response()->json($user->schools()->get(['schools.*', 'user_schools.nap']));
    }

    public function removeSchool(User $user, string $schoolId): JsonResponse
    {
        $user->schools()->detach($schoolId);

        return response()->json($user->schools()->get(['schools.*', 'user_schools.nap']));
    }

    protected static function rules(?string $id = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id,
            'password' => 'sometimes|string|min:6',
            'role' => 'required|in:admin,orientador,professor,escola',
            'role_id' => 'nullable|exists:roles,id',
            'mundoz_user' => 'nullable|string|max:255',
            'mundoz_password' => 'nullable|string|max:255',
        ];
    }

    protected static function updateRules(?string $id = null): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$id,
            'password' => 'sometimes|string|min:6',
            'role' => 'sometimes|in:admin,orientador,professor,escola',
            'role_id' => 'nullable|exists:roles,id',
            'mundoz_user' => 'nullable|string|max:255',
            'mundoz_password' => 'nullable|string|max:255',
        ];
    }
}