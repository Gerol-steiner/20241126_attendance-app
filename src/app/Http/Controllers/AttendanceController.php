<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 追加
use App\Models\Attendance; // 追加

class AttendanceController extends Controller
{
    // 勤怠画面の表示
    public function index()
    {
        // 1. ユーザーがログインしていなければ、ログインページにリダイレクト
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // 2. ログインしているユーザーを取得
        $user = Auth::user();

        // 3. 今日の日付を取得 (Y-m-d形式)
        $today = now()->format('Y-m-d');

        // 4. 現在のユーザーの今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first(); // 該当レコードを取得

        // 5. 勤怠画面 (attendance.blade.php) を表示
        return view('attendance', [
            'user' => $user,
            'attendance' => $attendance, // 取得した勤怠レコードを渡す
        ]);
    }

    //「出勤ボタン」押下
    public function checkIn(Request $request)
    {
        $user = Auth::user(); // 現在ログイン中のユーザー

        // 出勤情報を保存
        $attendance = new Attendance();
        $attendance->user_id = $user->id;
        $attendance->date = now()->toDateString(); // 日付
        $attendance->check_in = now()->toTimeString(); // 出勤時刻
        $attendance->save();

        // attendance.blade.phpの<script>部でレスポンスを受け取りフラッシュメッセージをHTML要素としてビューに表示させる
        return response()->json(['message' => '出勤登録が完了しました']);
    }
}
