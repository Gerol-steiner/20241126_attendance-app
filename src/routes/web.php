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

Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');

// ユーザー登録ルート
Route::post('/register', [RegisterController::class, 'register'])->name('register');

// メール認証待ちの仮登録完了メッセージ用のビュー
Route::get('/register/pending', function () {return view('auth.registration_pending');})->name('registration.pending');

// メール認証
// 一時的にログインさせてから、verifyメソッドを呼び出す
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'temporaryLoginAndVerify'])
    ->middleware(['signed'])  // 署名付きURLでの確認を行う
    ->name('verification.verify'); // 「VerifyEmail.php」でデフォルト定義されいるので変更しないこと




    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.checkIn');