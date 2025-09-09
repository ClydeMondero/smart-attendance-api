<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Example: teacher-only resource
    Route::middleware('role:teacher')->group(function () {
        Route::get('/teacher/dashboard', fn() => response()->json(['message' => 'Teacher dashboard']));
    });

    // Example: admin-only resource
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', fn() => response()->json(['message' => 'Admin dashboard']));
    });
});
