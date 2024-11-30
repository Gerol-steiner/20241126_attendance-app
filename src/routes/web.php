<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\AttendanceController;

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



// ユーザー登録ルート
Route::post('/register', [RegisterController::class, 'register'])->name('register');

// メール認証待ちの仮登録完了メッセージ用のビュー
Route::get('/register/pending', function () {return view('auth.registration_pending');})->name('registration.pending');

// メール認証
// 一時的にログインさせてから、verifyメソッドを呼び出す
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'temporaryLoginAndVerify'])
    ->middleware(['signed'])  // 署名付きURLでの確認を行う
    ->name('verification.verify'); // 「VerifyEmail.php」でデフォルト定義されいるので変更しないこと


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
// 「勤怠一覧画面」
Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
// 「勤怠詳細画面」
Route::get('/attendance/{id}', [AttendanceController::class, 'detail'])->name('attendance.detail');
// 勤怠修正申請を処理するルート
Route::post('/attendance/{id}/update', [AttendanceController::class, 'update'])->name('attendance.update');