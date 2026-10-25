<?php

use App\Http\Controllers\Api\V1\AttendanceRecordController;
use App\Http\Controllers\Api\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 認証済みユーザーの情報取得（既存ルート）
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// APIログイン（トークン発行）
Route::post('/login', [
    LoginController::class,
    'login',
]);

// 勤怠API v1
Route::prefix('v1')->group(function () {

    // 一覧取得（認証不要）
    Route::get('/attendance-records', [
        AttendanceRecordController::class,
        'index',
    ]);

    // 詳細取得（認証不要）
    Route::get('/attendance-records/{attendanceRecord}', [
        AttendanceRecordController::class,
        'show',
    ]);

    // 新規登録・更新・削除（認証必須）
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/attendance-records', [
            AttendanceRecordController::class,
            'store',
        ]);

        Route::put('/attendance-records/{attendanceRecord}', [
            AttendanceRecordController::class,
            'update',
        ]);

        Route::patch('/attendance-records/{attendanceRecord}', [
            AttendanceRecordController::class,
            'update',
        ]);

        Route::delete('/attendance-records/{attendanceRecord}', [
            AttendanceRecordController::class,
            'destroy',
        ]);
    });
});
