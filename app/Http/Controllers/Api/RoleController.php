<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Role::withCount('users')->orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(static::rules());

        return response()->json(Role::create($validated), 201);
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json($role->loadCount('users'));
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate(static::rules($role->id));

        $role->update($validated);

        return response()->json($role);
    }

    public function destroy(Role $role): JsonResponse
    {
        $role->delete();

        return response()->json(null, 204);
    }

    protected static function rules(?string $id = null): array
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name,'.$id,
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ];
    }
}