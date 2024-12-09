<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\AttendanceModificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// 【管理者】ログイン
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');

// 【管理者】ログアウト
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// 【管理者】勤怠一覧（日次）
Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'dailyList'])->name('admin.attendance.daily_list');

// 【管理者】スタッフ一覧
Route::get('/admin/staff/list', [StaffController::class, 'index'])->name('admin.staff.list');

// 【管理者】勤怠一覧（月次）
Route::get('/admin/attendance/staff/{id}', [AdminAttendanceController::class, 'monthlyList'])->name('admin.attendance.staff.monthly_list');

// 【管理者】修正申請の承認画面
// 【一般ユーザー】修正申請の承認待ち画面
Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [AttendanceModificationController::class, 'showApprovalForm'])
    ->name('attendance_modification.approve');

// 【管理者】修正申請の承認処理
Route::post('/stamp_correction_request/approve/{attendance_correct_request}', [AttendanceModificationController::class, 'approveModification'])
    ->name('attendance_modification.approve_request');



// 「申請一覧」の表示
Route::get('/stamp_correction_request/list', [AttendanceController::class, 'listRequests'])->name('admin.requests');




// ユーザー登録ルート
Route::post('/register', [RegisterController::class, 'register'])->name('register');

// メール認証待ちの仮登録完了メッセージ用のビュー
Route::get('/register/pending', function () {return view('auth.registration_pending');})->name('registration.pending');

// メール認証
// 一時的にログインさせてから、verifyメソッドを呼び出す
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'temporaryLoginAndVerify'])
    ->middleware(['signed'])  // 署名付きURLでの確認を行う
    ->name('verification.verify'); // 「VerifyEmail.php」でデフォルト定義されいるので変更しないこと

// ログイン（一般ユーザー）
Route::post('/login', [LoginController::class, 'login'])->name('login');


// 「勤怠画面」
Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
// 「勤怠画面」：出勤
Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.checkIn');
// 「勤怠画面」：休憩入
Route::post('/attendance/start-break', [AttendanceController::class, 'startBreak'])->name('attendance.startBreak');
// 「勤怠画面」：休憩戻
Route::post('/attendance/end-break', [AttendanceController::class, 'endBreak'])->name('attendance.endBreak');
// 「勤怠画面」：退勤
Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.checkOut');
// 「（月次）勤怠一覧画面」
Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
// 「勤怠詳細画面」
Route::get('/attendance/{id}', [AttendanceController::class, 'detail'])->name('attendance.detail');


// 勤怠修正申請を登録
Route::post('/attendance/update/{user_id}', [AttendanceModificationController::class, 'update'])->name('attendance.update');


