<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\AttendanceController;

// Pintu Masuk (Public)
Route::post('/login/admin', [AuthController::class, 'loginAdmin']);
Route::post('/login/guru', [AuthController::class, 'loginGuru']);

// Pintu Tertutup (Harus Punya Token)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('/teachers', TeacherController::class);
    Route::put('/teachers/{id}/rfid', [TeacherController::class, 'updateRfid']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/attendance', [AttendanceController::class, 'store']);

    // Cek User Login
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
