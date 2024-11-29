<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 追加
use App\Models\Attendance; // 追加
use App\Models\AttendanceStatusChange; // 追加
use App\Models\AttendanceStatus; // 追加
use Carbon\Carbon;


class AttendanceController extends Controller
{
    // 勤怠画面の表示
    public function index()
    {
        // ユーザーがログインしていなければ、ログインページにリダイレクト
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // ログインしているユーザーを取得
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', now()->toDateString())
            ->first();

        // デフォルトで「勤務外」を設定
        $currentStatus = '勤務外';

        // 勤怠レコードが存在する場合、勤務状態を取得
        if ($attendance) {
            // 最新のステータス変更を取得
            $lastStatusChange = $attendance->attendanceStatusChanges()->latest('changed_at')->first();

            if ($lastStatusChange) {
                $currentStatus = $lastStatusChange->attendanceStatus->name;
            }
        }

        // 勤怠画面を表示
        return view('attendance.index', compact('attendance', 'currentStatus'));
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

        // 勤務状態を記録
        $this->logAttendanceStatusChange($attendance->id, '出勤中');

        // 勤怠画面にリダイレクト
        return redirect()->route('attendance.index')->with('success', '出勤登録が完了しました');
    }

    //「休憩入ボタン」押下
    public function startBreak(Request $request)
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', now()->toDateString())
            ->firstOrFail();

        $attendance->break_start = now()->toTimeString();
        $attendance->save();

        // 勤務状態を記録
        $this->logAttendanceStatusChange($attendance->id, '休憩中');

        // 勤怠画面にリダイレクト
        return redirect()->route('attendance.index')->with('success', '休憩開始を記録しました');
    }

    //「休憩戻ボタン」押下
    public function endBreak(Request $request)
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', now()->toDateString())
            ->firstOrFail();

        $attendance->break_end = now()->toTimeString();
        $attendance->save();

        // 勤務状態を記録
        $this->logAttendanceStatusChange($attendance->id, '出勤中');

                // 勤怠画面にリダイレクト
        return redirect()->route('attendance.index')->with('success', '休憩終了を記録しました');
    }

    //「退勤ボタン」押下
    public function checkOut(Request $request)
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', now()->toDateString())
            ->firstOrFail();

        $attendance->check_out = now()->toTimeString();
        $attendance->save();

        // 勤務状態を記録
        $this->logAttendanceStatusChange($attendance->id, '退勤済');

                // 勤怠画面にリダイレクト
        return redirect()->route('attendance.index')->with('success', '退勤登録が完了しました');
    }

    // 勤務状態変更を記録するヘルパーメソッド
    private function logAttendanceStatusChange($attendanceId, $statusName)
    {
        // 勤務状態を取得
        $status = AttendanceStatus::where('name', $statusName)->firstOrFail();

        $change = new AttendanceStatusChange();
        $change->attendance_id = $attendanceId;
        $change->attendance_status_id = $status->id;
        $change->changed_at = now();
        $change->save();
    }

    // 勤怠一覧画面の表示
public function list(Request $request)
{
    $user = Auth::user();
    $currentMonth = $request->input('month', now()->format('Y-m'));
    $startDate = Carbon::createFromFormat('Y-m', $currentMonth)->startOfMonth();
    $endDate = $startDate->copy()->endOfMonth();

    // 一か月分の日付を生成
    $dates = [];
    for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
        $dates[] = $date->copy();
    }

    // 勤怠データを取得
    $attendances = Attendance::where('user_id', $user->id)
        ->whereBetween('date', [$startDate, $endDate])
        ->get()
        ->keyBy('date');

    // データを加工してビューに渡す
    $data = [];
    foreach ($dates as $date) {
        $attendance = $attendances->get($date->format('Y-m-d'));
        $breakTime = null;
        $breakMinutes = 0;

        if ($attendance && $attendance->break_start && $attendance->break_end) {
            $breakMinutes = Carbon::parse($attendance->break_start)->diffInMinutes(Carbon::parse($attendance->break_end));
            $hours = floor($breakMinutes / 60);
            $minutes = $breakMinutes % 60;
            $breakTime = sprintf('%d:%02d', $hours, $minutes); // "1:30" の形式にフォーマット
        }

        $totalTime = null;
        if ($attendance && $attendance->check_in && $attendance->check_out) {
            $totalMinutes = Carbon::parse($attendance->check_in)->diffInMinutes(Carbon::parse($attendance->check_out));
            $totalMinutes -= $breakMinutes; // 休憩時間を引く
            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
            $totalTime = sprintf('%d:%02d', $hours, $minutes); // "8:30" の形式にフォーマット
        }

        $data[] = [
            'date' => $date->format('m/d'),
            'day' => $date->format('D'),
            'attendance' => $attendance,
            'breakTime' => $breakTime,
            'totalTime' => $totalTime,
        ];
    }

    return view('attendance.list', [
        'currentMonth' => $startDate,
        'dates' => $dates,
        'attendances' => $attendances,
        'data' => $data,
    ]);
}




}
