<?php

use App\Http\Controllers\Api\Admin\CenterController as AdminCenterController;
use App\Http\Controllers\Api\Admin\DoctorController as AdminDoctorController;
use App\Http\Controllers\Api\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controllers\Api\Doctor\ChildController as DoctorChildController;
use App\Http\Controllers\Api\Doctor\NotificationController as DoctorNotificationController;
use App\Http\Controllers\Api\Doctor\ParentController as DoctorParentController;
use App\Http\Controllers\Api\Parent\AppointmentController as ParentAppointmentController;
use App\Http\Controllers\Api\Parent\ChildController as ParentChildController;
use App\Http\Controllers\Api\Parent\NotificationController as ParentNotificationController;
use App\Http\Controllers\Api\SharedController;
use Illuminate\Support\Facades\Route;

// مسارات المصادقة المشتركة لكل الأدوار
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('forgot-password/send-code', [AuthController::class, 'sendForgotPasswordCode']);
    Route::post('forgot-password/verify-code', [AuthController::class, 'verifyForgotPasswordCode']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// ============ مسارات المدير الوطني ============
Route::prefix('admin')->middleware('auth:admin')->group(function () {
    Route::apiResource('centers', AdminCenterController::class);
    Route::apiResource('doctors', AdminDoctorController::class);
    Route::get('parents', [AdminUserController::class, 'parents']);
    Route::get('children', [AdminUserController::class, 'children']);

    Route::get('inventory', [AdminInventoryController::class, 'index']);
    Route::post('inventory', [AdminInventoryController::class, 'store']);
    Route::get('inventory/{inventory}', [AdminInventoryController::class, 'show']);
    Route::put('inventory/{inventory}', [AdminInventoryController::class, 'update']);

    Route::get('reports', [AdminReportController::class, 'reports']);
    Route::get('audit', [AdminReportController::class, 'auditLogs']);

    // Settings & Backup
    Route::get('settings', [\App\Http\Controllers\AdminSettingsController::class, 'getSettings']);
    Route::post('settings', [\App\Http\Controllers\AdminSettingsController::class, 'updateSettings']);
    Route::post('settings/change-password', [\App\Http\Controllers\AdminSettingsController::class, 'changePassword']);
    Route::post('settings/kill-sessions', [\App\Http\Controllers\AdminSettingsController::class, 'killSessions']);
    Route::get('backup', [\App\Http\Controllers\AdminSettingsController::class, 'backup']);
});

// المسارات المشتركة (عامة)
Route::get('centers', [\App\Http\Controllers\Api\SharedController::class, 'centers']);
Route::get('vaccines', [\App\Http\Controllers\Api\SharedController::class, 'vaccines']);

// المسارات التي تحتاج لتسجيل الدخول (إضافية)
Route::middleware('auth:sanctum')->group(function () {
    // يمكن وضع مسارات مشتركة هنا لو وجدت مستقبلاً
});

// ============ مسارات الطبيب ============
Route::prefix('doctor')->middleware('auth:doctor')->group(function () {
    Route::get('children', [DoctorChildController::class, 'index']);
    Route::post('children', [DoctorChildController::class, 'store']);
    Route::put('children/{child}', [DoctorChildController::class, 'update']);
    Route::get('children/scan/{qrCode}', [DoctorChildController::class, 'scan']);
    Route::get('children/{child}/qr', [DoctorChildController::class, 'qr']);
    Route::get('children/{child}', [DoctorChildController::class, 'show']);
    Route::delete('children/{child}', [DoctorChildController::class, 'destroy']);
    Route::get('parents', [DoctorParentController::class, 'index']);
    Route::get('parents/{national_id}', [DoctorParentController::class, 'show']);
    Route::post('parents', [DoctorParentController::class, 'store']);
    Route::put('parents/{parent}', [DoctorParentController::class, 'update']);

    Route::get('appointments', [DoctorAppointmentController::class, 'index']);
    Route::get('notifications', [DoctorNotificationController::class, 'index']);
    Route::post('notifications', [DoctorNotificationController::class, 'sendManual']);
    Route::put('appointments/{appointment}/complete', [DoctorAppointmentController::class, 'complete']);
    Route::post('appointments', [DoctorAppointmentController::class, 'store']);
    Route::put('appointments/{appointment}', [DoctorAppointmentController::class, 'update']);
    Route::delete('appointments/{appointment}', [DoctorAppointmentController::class, 'destroy']);
});

// ============ مسارات ولي الأمر ============
Route::prefix('parent')->middleware('auth:parent')->group(function () {
    Route::get('children', [ParentChildController::class, 'index']);
    Route::get('children/{childId}/qr', [ParentChildController::class, 'qr']);
    Route::post('children', [ParentChildController::class, 'store']);
    Route::put('children/{childId}', [ParentChildController::class, 'update']);

    Route::get('children/{childId}/appointments', [ParentAppointmentController::class, 'index']);
    Route::get('notifications', [ParentNotificationController::class, 'index']);
    Route::put('notifications/read-all', [ParentNotificationController::class, 'markAllRead']);
    Route::put('notifications/{notification}/read', [ParentNotificationController::class, 'markRead']);
    Route::put('appointments/{appointment}/reschedule', [ParentAppointmentController::class, 'reschedule']);
    Route::put('appointments/{appointment}/confirm', [ParentAppointmentController::class, 'confirm']);
    Route::put('appointments/{appointment}/cancel', [ParentAppointmentController::class, 'cancel']);

    Route::post('settings/password/send-code', [\App\Http\Controllers\Api\Parent\SettingsController::class, 'sendCode']);
    Route::post('settings/password/verify-code', [\App\Http\Controllers\Api\Parent\SettingsController::class, 'verifyCode']);
    Route::post('settings/password/change', [\App\Http\Controllers\Api\Parent\SettingsController::class, 'changePassword']);
    Route::post('settings/change-center', [\App\Http\Controllers\Api\Parent\SettingsController::class, 'changeCenter']);
});
