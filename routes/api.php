<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\GradeController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/settings', SettingController::class);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('classes', ClassController::class);

    Route::get('attendances/stats', [AttendanceController::class, 'stats']);
    Route::apiResource('attendances', AttendanceController::class);

    Route::apiResource('students', StudentController::class);

    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('grades', GradeController::class);


    Route::prefix('dashboard')->group(function () {
        Route::get('/summary', [DashboardController::class, 'summary']);
        Route::get('/trend', [DashboardController::class, 'trend']);
        Route::get('/classes', [DashboardController::class, 'classes']);
        Route::get('/logs', [DashboardController::class, 'logs']);
    });

    //  teacher-only resource
    Route::middleware('role:teacher')->group(function () {
        Route::get('/teacher/dashboard', fn() => response()->json(['message' => 'Teacher dashboard']));
    });

    //  admin-only resource
    Route::middleware('role:admin')->group(function () {

        Route::put('/users/{user}/status', [UserController::class, 'toggleStatus']);
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
        Route::post('/reset-password', [UserController::class, 'handleReset']);
        Route::apiResource('users', UserController::class);

        Route::post('/register', [AuthController::class, 'register']);


        Route::get('/admin/dashboard', fn() => response()->json(['message' => 'Admin dashboard']));
    });
});
