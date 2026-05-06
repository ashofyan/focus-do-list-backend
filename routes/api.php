<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\MilestoneController;
use App\Http\Controllers\Api\SubTaskController;
use App\Http\Controllers\Api\TodoController;
use Illuminate\Support\Facades\Route;

Route::pattern('id', '[0-9]+');
Route::pattern('sid', '[0-9]+');

/*
|--------------------------------------------------------------------------
| API Routes — Todo Management System
|--------------------------------------------------------------------------
| Semua route API menggunakan prefix /api (di bootstrap/app.php)
| Auth menggunakan AuthService eksternal (Bearer token)
|--------------------------------------------------------------------------
*/

// ============================================================
// PUBLIC ROUTES (tanpa auth)
// ============================================================
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// ============================================================
// PROTECTED ROUTES (wajib login via AuthService)
// ============================================================
Route::middleware('auth.service')->group(function () {

    // ----------------------------------------------------------
    // Auth
    // ----------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me',     [AuthController::class, 'me']);
        Route::put('me',     [AuthController::class, 'updateProfile']);
        Route::put('password',     [AuthController::class, 'updatePassword']);
    });

    // ----------------------------------------------------------
    // Todos
    // ----------------------------------------------------------
    Route::prefix('todos')->group(function () {

        // List & create
        Route::get('/',     [TodoController::class, 'index']);   // GET  /api/todos?status=pending&date=today&group_id=1
        Route::post('/',    [TodoController::class, 'store']);   // POST /api/todos

        // Khusus: today & pinned (sebelum {id} agar tidak konflik)
        Route::get('today',  [TodoController::class, 'today']);  // GET  /api/todos/today
        Route::get('pinned', [TodoController::class, 'pinned']); // GET  /api/todos/pinned

        // CRUD by ID
        Route::get('{id}',    [TodoController::class, 'show']);    // GET    /api/todos/{id}
        Route::put('{id}',    [TodoController::class, 'update']);  // PUT    /api/todos/{id}
        Route::delete('{id}', [TodoController::class, 'destroy']); // DELETE /api/todos/{id}

        // Actions
        Route::patch('{id}/complete', [TodoController::class, 'complete']); // PATCH /api/todos/{id}/complete
        Route::patch('{id}/pin',      [TodoController::class, 'togglePin']); // PATCH /api/todos/{id}/pin

        // Sub-tasks
        Route::post('{id}/sub-tasks',         [SubTaskController::class, 'store']);   // POST
        Route::patch('{id}/sub-tasks/{sid}',  [SubTaskController::class, 'toggle']);  // PATCH toggle complete
        Route::put('{id}/sub-tasks/{sid}',    [SubTaskController::class, 'update']);  // PUT rename
        Route::delete('{id}/sub-tasks/{sid}', [SubTaskController::class, 'destroy']); // DELETE

    });

    // ----------------------------------------------------------
    // Groups
    // ----------------------------------------------------------
    Route::apiResource('groups', GroupController::class)->parameters(['groups' => 'id']);

    // ----------------------------------------------------------
    // Labels
    // ----------------------------------------------------------
    Route::apiResource('labels', LabelController::class)->except(['show'])->parameters(['labels' => 'id']);

    // ----------------------------------------------------------
    // Milestones
    // ----------------------------------------------------------
    Route::apiResource('milestones', MilestoneController::class)->parameters(['milestones' => 'id']);
    Route::patch('milestones/{id}/progress', [MilestoneController::class, 'updateProgress']);

});
