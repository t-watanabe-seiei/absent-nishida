<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\ParentLoginController;
use App\Http\Controllers\Auth\StudentLoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\ParentController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\CsvImportController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AbsenceController as AdminAbsenceController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Parent\AbsenceController;
use App\Http\Controllers\Parent\AnnouncementController as ParentAnnouncementController;
use App\Http\Controllers\DemoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// デモモード（試作段階用）
Route::middleware('web')->group(function () {
    Route::get('/demo/parent-login', [DemoController::class, 'parentLogin']);
});

// 登録
Route::middleware('web')->group(function () {
    Route::post('/register/verify-classroom', [RegisterController::class, 'verifyClassroom']);
    Route::post('/register/parent', [RegisterController::class, 'registerParent']);
});

// 管理者認証ルート
Route::prefix('admin')->middleware('web')->group(function () {
    // ログイン・認証
    Route::post('/login', [AdminLoginController::class, 'login']);
    Route::post('/verify-2fa', [AdminLoginController::class, 'verify2FA']);
    
    // 認証が必要なルート
    Route::middleware(['admin.auth', 'two_factor'])->group(function () {
        Route::post('/logout', [AdminLoginController::class, 'logout']);
        Route::get('/me', [AdminLoginController::class, 'me']);
        
        // ダッシュボード
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        
        // クラス管理
        Route::get('/classes', [ClassController::class, 'index']);
        Route::post('/classes', [ClassController::class, 'store']);
        Route::get('/classes/{id}', [ClassController::class, 'show']);
        Route::put('/classes/{id}', [ClassController::class, 'update']);
        Route::delete('/classes/{id}', [ClassController::class, 'destroy']);
        
        // 生徒管理
        Route::get('/students', [StudentController::class, 'index']);
        Route::post('/students', [StudentController::class, 'store']);
        Route::get('/students/{id}', [StudentController::class, 'show']);
        Route::put('/students/{id}', [StudentController::class, 'update']);
        Route::delete('/students/{id}', [StudentController::class, 'destroy']);
        
        // 保護者管理
        Route::get('/parents', [ParentController::class, 'index']);
        Route::post('/parents', [ParentController::class, 'store']);
        Route::get('/parents/{id}', [ParentController::class, 'show']);
        Route::put('/parents/{id}', [ParentController::class, 'update']);
        Route::delete('/parents/{id}', [ParentController::class, 'destroy']);
        
        // 欠席情報管理
        Route::get('/absences/stats', [AdminAbsenceController::class, 'stats']);
        Route::get('/absences/monthly', [AdminAbsenceController::class, 'monthly']);
        Route::get('/absences/today', [AdminAbsenceController::class, 'today']);
        Route::get('/absences/export', [AdminAbsenceController::class, 'export']);
        Route::get('/absences', [AdminAbsenceController::class, 'index']);
        Route::get('/absences/{id}', [AdminAbsenceController::class, 'show']);
        
        // CSVインポート
        Route::post('/import/students', [CsvImportController::class, 'importStudents']);
        Route::post('/import/parents', [CsvImportController::class, 'importParents']);
        Route::post('/import/admins', [CsvImportController::class, 'importAdmins']);
        Route::post('/import/student-classes', [CsvImportController::class, 'importStudentClasses']);
        Route::get('/import/template/{type}', [CsvImportController::class, 'downloadTemplate']);
        
        // 旧インポート（後方互換性）
        Route::post('/import/classes', [ImportController::class, 'importClasses']);
        Route::post('/import/teachers', [ImportController::class, 'importTeachers']);
        Route::post('/import/parents-v2', [ImportController::class, 'importParents']); // 新仕様の保護者インポート

        // お知らせ管理
        Route::get('/announcements', [AdminAnnouncementController::class, 'index']);
        Route::post('/announcements', [AdminAnnouncementController::class, 'store']);
        Route::get('/announcements/{id}', [AdminAnnouncementController::class, 'show']);
        Route::put('/announcements/{id}', [AdminAnnouncementController::class, 'update']);
        Route::delete('/announcements/{id}', [AdminAnnouncementController::class, 'destroy']);
        Route::get('/announcements/{id}/reads', [AdminAnnouncementController::class, 'readStatus']);
        Route::post('/announcements/{id}/attachments', [AdminAnnouncementController::class, 'addAttachment']);
        Route::delete('/announcements/{id}/attachments/{attachId}', [AdminAnnouncementController::class, 'removeAttachment']);

        // システム設定
        Route::get('/settings', [SettingController::class, 'index']);
        Route::put('/settings', [SettingController::class, 'update']);
    });
});

// 生徒認証ルート
Route::prefix('student')->middleware('web')->group(function () {
    // ログイン・認証
    Route::post('/login', [StudentLoginController::class, 'login']);
    Route::post('/verify-2fa', [StudentLoginController::class, 'verify2FA']);
});

// 保護者認証ルート
Route::prefix('parent')->middleware('web')->group(function () {
    // ログイン・認証
    Route::post('/login', [ParentLoginController::class, 'login']);
    Route::post('/register-email', [ParentLoginController::class, 'registerEmail']); // 初回ログイン時のメール登録
    Route::post('/verify-2fa', [ParentLoginController::class, 'verify2FA']);
    Route::post('/resend-2fa', [ParentLoginController::class, 'resend2FA']);
    
    // 認証が必要なルート
    Route::middleware(['parent.auth', 'two_factor'])->group(function () {
        Route::post('/logout', [ParentLoginController::class, 'logout']);
        Route::get('/me', [ParentLoginController::class, 'me']);
        Route::post('/request-email-change', [ParentLoginController::class, 'requestEmailChange']);
        Route::post('/confirm-email-change', [ParentLoginController::class, 'confirmEmailChange']);
        
        // 欠席連絡管理
        Route::get('/absences', [AbsenceController::class, 'index']);
        Route::post('/absences', [AbsenceController::class, 'store']);
        Route::get('/absences/{id}', [AbsenceController::class, 'show']);
        Route::put('/absences/{id}', [AbsenceController::class, 'update']);
        Route::delete('/absences/{id}', [AbsenceController::class, 'destroy']);

        // お知らせ
        Route::get('/announcements', [ParentAnnouncementController::class, 'index']);
        Route::post('/announcements/{id}/read', [ParentAnnouncementController::class, 'read']);
        Route::get('/announcements/{id}/attachments/{attachId}', [ParentAnnouncementController::class, 'downloadAttachment']);
    });
});
