<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RequestAttendanceController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffAttendanceController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\RequestAttendanceController as AdminRequestAttendanceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 管理者ログイン
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])
    ->name('admin.login');

Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->name('admin.login.store');


// ログアウト
Route::post('/logout', [AdminAuthController::class, 'logout'])
    ->name('logout');

Route::post('/admin/logout', [AdminAuthController::class, 'logout'])
    ->name('admin.logout');

// 一般ユーザー
Route::middleware(['auth', 'verified'])->group(function () {

    // 勤怠登録画面
    Route::get('/attendance', [AttendanceController::class, 'create'])
        ->name('attendance.create');

    // 出勤・退勤・休憩入・休憩戻
    Route::post('/attendance', [AttendanceController::class, 'clock'])
        ->name('attendance.clock');

    // 勤怠一覧
    Route::get('/attendance/list', [AttendanceController::class, 'index'])
        ->name('attendance.index');

    // ★ 勤怠レポートを先に定義する
    Route::get('/attendance/report', [ReportController::class, 'index'])
        ->name('attendance.report');

    // 勤怠詳細
    Route::get('/attendance/{attendance}', [AttendanceController::class, 'show'])
        ->name('attendance.show');

    // 勤怠修正申請
    Route::post('/attendance/{attendance}', [RequestAttendanceController::class, 'store'])
        ->name('attendance.request.store');

    // 申請一覧
    Route::get(
        '/stamp_correction_request/list',
        [RequestAttendanceController::class, 'index']
    )->name('application.list');

    // 申請詳細
    Route::get(
        '/application/{id}',
        [RequestAttendanceController::class, 'show']
    )->name('application.show');
});

// 管理者
Route::middleware(['auth', 'admin'])->group(function () {

    // 勤怠一覧
    Route::get(
        '/admin/attendance/list',
        [AdminAttendanceController::class, 'index']
    )->name('admin.attendance.list');

    // スタッフ一覧
    Route::get(
        '/admin/staff/list',
        [StaffController::class, 'index']
    )->name('admin.staff.list');

    // スタッフ勤怠一覧
    Route::get(
        '/admin/attendance/staff/{user}',
        [StaffAttendanceController::class, 'index']
    )->name('admin.attendance.staff');

    // スタッフ勤怠CSV出力
    Route::get(
        '/admin/attendance/staff/{user}/export',
        [StaffAttendanceController::class, 'export']
    )->name('admin.attendance.staff.export');

    // スタッフ勤怠CSV出力（既存フォーム用）
    Route::post(
        '/export',
        [StaffAttendanceController::class, 'export']
    )->name('admin.attendance.staff.export.post');

    Route::get(
        '/stamp_correction_request/approve/{id}',
        [AdminRequestAttendanceController::class, 'show']
    )->name('admin.application.show');

    Route::post(
        '/stamp_correction_request/approve/{id}',
        [AdminRequestAttendanceController::class, 'approve']
    )->name('admin.application.approve');
});
