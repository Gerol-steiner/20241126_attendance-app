<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 追加
use App\Models\Attendance; // 追加
use App\Models\StatusChange; // 追加
use App\Models\Status; // 追加
use App\Models\BreakTime; // 追加
use Carbon\Carbon; // 追加


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
            $lastStatusChange = $attendance->StatusChanges()->latest('changed_at')->first();

            if ($lastStatusChange) {
                $currentStatus = $lastStatusChange->Status->name;
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
        $this->logStatusChange($attendance->id, '出勤中');

        // 勤怠画面にリダイレクト
        return redirect()->route('attendance.index')->with('success', '出勤登録が完了しました');
    }

    //「休憩入ボタン」押下
    public function startBreak(Request $request)
    {
        // 1. 該当する勤怠レコードを取得
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', now()->toDateString())
            ->firstOrFail();

        // 2. breaktimes テーブルに新しい休憩開始記録を作成
        $breaktime = new Breaktime();
        $breaktime->attendance_id = $attendance->id;
        $breaktime->break_start = now()->toTimeString(); // 現在の時間を休憩開始時間として格納
        $breaktime->save();

        // 3. 勤務状態を記録
        $this->logStatusChange($attendance->id, '休憩中');

        // 4. 勤怠画面にリダイレクト
        return redirect()->route('attendance.index')->with('success', '休憩開始を記録しました');
    }


    //「休憩戻ボタン」押下
    public function endBreak(Request $request)
    {
        // 1. 該当する勤怠レコードを取得
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', now()->toDateString())
            ->firstOrFail();

        // 2. breaktimes テーブルから該当レコードを取得（最新の休憩レコード）
        $breaktime = Breaktime::where('attendance_id', $attendance->id)
            ->whereNull('break_end') // まだ休憩終了時間が記録されていないレコード
            ->latest('break_start') // 最新の休憩開始時間を取得
            ->firstOrFail();

        // 3. break_end カラムに現在の時刻を設定
        $breaktime->break_end = now()->toTimeString();
        $breaktime->save();

        // 4. 勤務状態を記録
        $this->logStatusChange($attendance->id, '出勤中');

        // 5. 勤怠画面にリダイレクト
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
        $this->logStatusChange($attendance->id, '退勤済');

                // 勤怠画面にリダイレクト
        return redirect()->route('attendance.index')->with('success', '退勤登録が完了しました');
    }

    // 勤務状態変更を記録するヘルパーメソッド
    private function logStatusChange($attendanceId, $statusName)
    {
        // 勤務状態を取得
        $status = Status::where('name', $statusName)->firstOrFail();

        $change = new StatusChange();
        $change->attendance_id = $attendanceId;
        $change->status_id = $status->id;
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
            ->with('breaktimes') // Breaktime モデルをロード
            ->get()
            ->keyBy('date');

        // データを加工してビューに渡す
        $data = [];
        foreach ($dates as $date) {
            $attendance = $attendances->get($date->format('Y-m-d'));
            $breakTime = null;
            $breakMinutes = 0;

            if ($attendance) {
                // 休憩時間の計算（breaktimes テーブルから集計）
                foreach ($attendance->breaktimes as $breaktime) {
                    if ($breaktime->break_start && $breaktime->break_end) {
                        $breakMinutes += Carbon::parse($breaktime->break_start)
                            ->diffInMinutes(Carbon::parse($breaktime->break_end));
                    }
                }
                if ($breakMinutes > 0) {
                    $hours = floor($breakMinutes / 60);
                    $minutes = $breakMinutes % 60;
                    $breakTime = sprintf('%d:%02d', $hours, $minutes); // "1:30" の形式
                }
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

    // 「勤怠詳細」画面の表示
    public function detail($id)
    {
        // ログインしていなければログイン画面にリダイレクト
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $attendance = Attendance::findOrFail($id); // 該当の勤怠レコードを取得
        $user = $attendance->user; // 勤怠レコードに関連するユーザーを取得

        // dateをCarbonインスタンスに変換
        $attendance->date = Carbon::parse($attendance->date);

        return view('attendance.detail', compact('attendance', 'user'));
    }

    // 勤怠の「修正申請」
    public function update(Request $request, $id)
    {
        // POSTデータを取得
        $dateYear = $request->input('date_year');
        $dateMonthDay = $request->input('date_month_day');
        $checkIn = $request->input('check_in');
        $checkOut = $request->input('check_out');
        $breakStart = $request->input('break_start');
        $breakEnd = $request->input('break_end');
        $remarks = $request->input('remarks');

        // 日付を「Y-m-d」の形式に加工
        $date = Carbon::createFromFormat('Y年m月d日', $dateYear . $dateMonthDay)->format('Y-m-d');

        // 該当するattendanceレコードを取得
        $attendance = Attendance::where('date', $date)->first();

        if (!$attendance) {
            return redirect()->route('attendance.index')->with('error', '該当する勤怠レコードが存在しません。');
        }

        // attendance_modificationsテーブルに新しいレコードを作成
        $modification = new AttendanceModification();
        $modification->attendance_id = $attendance->id;
        $modification->date = $date;
        $modification->check_in = $checkIn;
        $modification->check_out = $checkOut;
        $modification->break_start = $breakStart;
        $modification->break_end = $breakEnd;
        $modification->remarks = $remarks;
        $modification->save();

        // 成功時にリダイレクト
        return redirect()->route('attendance.index')->with('success', '修正申請が完了しました。');
    }
}
