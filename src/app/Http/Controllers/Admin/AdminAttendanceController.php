<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    public function dailyList(Request $request)
    {
                // ログインしていなければログインページにリダイレクト
        if (!auth()->check()) {
            return redirect()->route('admin.login');
        }

        $currentDate = $request->input('date', now()->format('Y-m-d'));
        $currentDate = Carbon::createFromFormat('Y-m-d', $currentDate);

        $isToday = $currentDate->isToday();

        // 管理者以外の全ユーザーを取得
        $users = User::where('is_admin', 0)->get();

        // 該当日の勤怠データを取得
        $attendances = Attendance::whereDate('date', $currentDate)
            ->with(['user', 'breaktimes'])
            ->get()
            ->keyBy('user_id'); // ユーザーIDでキー化

        $data = [];
        foreach ($users as $user) {
            $attendance = $attendances->get($user->id);

            $breakTime = null;
            $breakMinutes = null;

            if ($attendance) {
                foreach ($attendance->breaktimes as $breaktime) {
                    if (!empty($breaktime->break_start) && !empty($breaktime->break_end)) {
                        $breakMinutes += Carbon::parse($breaktime->break_start)
                            ->diffInMinutes(Carbon::parse($breaktime->break_end));
                    }
                }
            }

                // 休憩時間が存在する場合のみ休憩時間をフォーマット
                if ($breakMinutes !== null) { // 計算結果がある場合のみフォーマット
                    $hours = floor($breakMinutes / 60);
                    $minutes = $breakMinutes % 60;
                    $breakTime = sprintf('%d:%02d', $hours, $minutes); // "1:30" の形式
                }

            $totalTime = null;
            if ($attendance && !empty($attendance->check_in) && !empty($attendance->check_out)) {
                $totalMinutes = Carbon::parse($attendance->check_in)->diffInMinutes(Carbon::parse($attendance->check_out));
                $totalMinutes -= $breakMinutes;
                $totalTime = sprintf('%d:%02d', floor($totalMinutes / 60), $totalMinutes % 60);
            }

            $data[] = [
                'name' => $user->name,
                'check_in' => $attendance && $attendance->check_in
                    ? Carbon::parse($attendance->check_in)->format('H:i')
                    : '',
                'check_out' => $attendance && $attendance->check_out
                    ? Carbon::parse($attendance->check_out)->format('H:i')
                    : '',
                'breakTime' => $attendance ? $breakTime : '',
                'totalTime' => $attendance ? ($totalTime ?? '') : '',
                'attendance_id' => $attendance ? $attendance->id : null,
            ];
        }

        return view('admin.attendance_daily_list', [
            'currentDate' => $currentDate,
            'isToday' => $isToday,
            'data' => $data,
        ]);
    }

    // 該当スタッフの月次勤怠の表示
    public function monthlyList(Request $request, $id)
    {
        // ログインしていなければログインページにリダイレクト
        if (!auth()->check()) {
            return redirect()->route('admin.login');
        }

        // 該当スタッフのユーザーデータを取得。存在しない場合は404エラー
        $user = User::findOrFail($id);

        // リクエストから指定された月を取得。指定がなければ現在の月を使用
        $currentMonth = $request->input('month', now()->format('Y-m'));

        // 月初と月末の日付を取得
        $startDate = Carbon::createFromFormat('Y-m', $currentMonth)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // 月の日付をすべて生成して配列に格納
        $dates = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates[] = $date->copy();
        }

        // 該当スタッフの勤怠データを取得
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate]) // 月内の日付に絞る
            ->with('breaktimes') // 関連する休憩時間をロード
            ->get()
            ->keyBy('date'); // 日付をキーにしてコレクションを作成

        // 勤怠データを加工して表示用データを作成
        $data = [];
        foreach ($dates as $date) {
            // 現在の日付に対応する勤怠データを取得
            $attendance = $attendances->get($date->format('Y-m-d'));
            $breakTime = null; // 休憩時間の初期値
            $breakMinutes = null; // 休憩時間（分）の初期値

            if ($attendance) {
                // 勤怠データがある場合、関連する休憩時間を計算
                foreach ($attendance->breaktimes as $breaktime) {
                    if (!empty($breaktime->break_start) && !empty($breaktime->break_end)) {
                        $breakMinutes += Carbon::parse($breaktime->break_start)
                            ->diffInMinutes(Carbon::parse($breaktime->break_end));
                    }
                }
                // 計算された休憩時間がある場合、フォーマットして表示用に変換
                if ($breakMinutes !== null) {
                    $hours = floor($breakMinutes / 60);
                    $minutes = $breakMinutes % 60;
                    $breakTime = sprintf('%d:%02d', $hours, $minutes); // 時間と分の形式
                }
            }

            // 合計勤務時間の計算
            $totalTime = null; // 初期値
            if ($attendance && $attendance->check_in && $attendance->check_out) {
                // 出勤・退勤が記録されている場合、勤務時間を計算
                $totalMinutes = Carbon::parse($attendance->check_in)->diffInMinutes(Carbon::parse($attendance->check_out));
                $totalMinutes -= $breakMinutes; // 休憩時間を差し引く
                $hours = floor($totalMinutes / 60);
                $minutes = $totalMinutes % 60;
                $totalTime = sprintf('%d:%02d', $hours, $minutes); // 時間と分の形式
            }

            // 表示用データに加工したデータを追加
            $data[] = [
                'date' => $date->format('m/d'), // 日付
                'day' => $date->format('D'), // 曜日（英語）
                'attendance' => $attendance, // 勤怠データ
                'breakTime' => $breakTime, // 休憩時間
                'totalTime' => $totalTime, // 勤務合計時間
            ];
        }

        // ビューに必要なデータを渡してレンダリング
        return view('admin.staff_monthly_attendance', [
            'currentMonth' => $startDate, // 現在の月
            'user' => $user, // 該当スタッフ
            'data' => $data, // 勤怠データ
        ]);
    }

}
