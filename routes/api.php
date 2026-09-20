<?php

use App\Http\Controllers\Api\AgendaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\NapItemController;
use App\Http\Controllers\Api\OrientadorScheduleController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SchoolClassController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\SegmentConfigController;
use App\Http\Controllers\Api\TbrCategoryController;
use App\Http\Controllers\Api\TbrTeamController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/session', [AuthController::class, 'session']);
    Route::get('/me', [AuthController::class, 'session']);

    Route::apiResource('schools', SchoolController::class);
    Route::get('/schools/{school}/users', [SchoolController::class, 'users']);
    Route::put('/schools/{school}/users', [SchoolController::class, 'replaceUsers']);

    Route::apiResource('classes', SchoolClassController::class);
    Route::apiResource('rooms', RoomController::class);
    Route::apiResource('schedules', ScheduleController::class);

    Route::apiResource('orientador-schedules', OrientadorScheduleController::class);
    Route::delete('/orientador-schedules/by-school/{schoolId}', [OrientadorScheduleController::class, 'destroyBySchool']);

    Route::apiResource('segment-configs', SegmentConfigController::class);
    Route::post('/segment-configs/upsert', [SegmentConfigController::class, 'upsert']);
    Route::delete('/segment-configs/by-school/{schoolId}', [SegmentConfigController::class, 'destroyBySchool']);

    Route::apiResource('items', ItemController::class);

    Route::apiResource('nap-items', NapItemController::class);
    Route::post('/nap-items/upsert', [NapItemController::class, 'upsert']);
    Route::delete('/nap-items/by-school/{schoolId}', [NapItemController::class, 'destroyBySchool']);
    Route::delete('/nap-items/by-school-and-year/{schoolId}/{year}', [NapItemController::class, 'destroyBySchoolAndYear']);

    Route::apiResource('agenda', AgendaController::class);

    Route::apiResource('tbr-categories', TbrCategoryController::class);

    Route::apiResource('tbr-teams', TbrTeamController::class);
    Route::put('/tbr-teams/replace-for-school/{schoolId}', [TbrTeamController::class, 'replaceForSchool']);
    Route::delete('/tbr-teams/by-school/{schoolId}', [TbrTeamController::class, 'destroyBySchool']);

    Route::apiResource('users', UserController::class);
    Route::get('/users/{user}/schools', [UserController::class, 'schools']);
    Route::put('/users/{user}/schools', [UserController::class, 'replaceSchools']);
    Route::post('/users/{user}/schools', [UserController::class, 'addSchool']);
    Route::delete('/users/{user}/schools/{schoolId}', [UserController::class, 'removeSchool']);

    Route::apiResource('roles', RoleController::class);
});
