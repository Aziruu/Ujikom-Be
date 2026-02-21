<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\MajorController;
use App\Http\Controllers\Api\SubjectController;

// Pintu Masuk (Public)
Route::post('/login/admin', [AuthController::class, 'loginAdmin']);
Route::post('/login/guru', [AuthController::class, 'loginGuru']);

// Pintu Tertutup (Harus Punya Token)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('/teachers', TeacherController::class);
    Route::get('/attendance/history', [AttendanceController::class, 'index']);
    Route::put('/teachers/{id}/rfid', [TeacherController::class, 'updateRfid']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/attendance', [AttendanceController::class, 'store']);

    Route::post('/leaves', [LeaveController::class, 'store']); // Guru ajukan
    Route::get('/leaves', [LeaveController::class, 'index']);  // Admin lihat
    Route::put('/leaves/{id}/verify', [LeaveController::class, 'verify']); // Admin verifikasi

    Route::put('/teachers/{id}/face', [App\Http\Controllers\Api\TeacherController::class, 'updateFace']);

    // Master Data
    Route::apiResource('academic-years', AcademicYearController::class);
    Route::apiResource('majors', MajorController::class);
    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('classrooms', ClassroomController::class);

    // Cek User Login
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
