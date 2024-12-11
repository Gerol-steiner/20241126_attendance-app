<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 追加
use App\Models\Attendance; // 追加
use App\Models\StatusChange; // 追加
use App\Models\Status; // 追加
use App\Models\BreakTime; // 追加
use Carbon\Carbon; // 追加
use App\Models\AttendanceModification; // 追加


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
            // 最新のステータス変更を取得（changed_at と id を基準にソート）
            $lastStatusChange = $attendance->StatusChanges()
                ->orderBy('changed_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();

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
                // ログインしていなければログイン画面にリダイレクト
        if (!Auth::check()) {
            return redirect()->route('login');
        }

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

        // 勤怠修正申請を取得
        $modifications = AttendanceModification::whereIn('attendance_id', $attendances->pluck('id'))
            ->with('attendance')
            ->get()
            ->groupBy('attendance_id');

        // データを加工してビューに渡す
        $data = [];
        foreach ($dates as $date) {
            $attendance = $attendances->get($date->format('Y-m-d'));
            $latestModification = null;

            if ($attendance && isset($modifications[$attendance->id])) {
                $latestModification = $modifications[$attendance->id]->sortByDesc('created_at')->first();
            }

            $breakTime = null;
            $totalTime = null;

            if ($attendance) {
                // 休憩時間の計算
                $breakMinutes = 0;
                foreach ($attendance->breaktimes as $breaktime) {
                    if ($breaktime->break_start && $breaktime->break_end) {
                        $breakMinutes += Carbon::parse($breaktime->break_start)
                            ->diffInMinutes(Carbon::parse($breaktime->break_end));
                    }
                }
                if ($breakMinutes > 0) {
                    $hours = floor($breakMinutes / 60);
                    $minutes = $breakMinutes % 60;
                    $breakTime = sprintf('%d:%02d', $hours, $minutes);
                }

                // 合計勤務時間の計算
                if ($attendance->check_in && $attendance->check_out) {
                    $totalMinutes = Carbon::parse($attendance->check_in)
                        ->diffInMinutes(Carbon::parse($attendance->check_out));

                    $totalMinutes -= $breakMinutes;
                    $hours = floor($totalMinutes / 60);
                    $minutes = $totalMinutes % 60;
                    $totalTime = sprintf('%d:%02d', $hours, $minutes);
                }
            }

            $data[] = [
                'date' => $date->format('m/d'),
                'day' => $date->format('D'),
                'attendance' => $attendance,
                'latestModification' => $latestModification,
                'breakTime' => $breakTime,
                'totalTime' => $totalTime,
            ];
        }


    return view('attendance.list', [
        'currentMonth' => $startDate,
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

        // 該当の「勤怠レコード」および「breaktimesレコード」をコレクションとして取得
        $attendance = Attendance::with('breaktimes')->findOrFail($id);

        // 勤怠レコードに関連するユーザーを取得
        $user = $attendance->user;

        // 「check_in」「check_out」について「時間：分」の形式に変換
        $attendance->check_in = $attendance->check_in
            ? Carbon::parse($attendance->check_in)->format('H:i')
            : null;

        $attendance->check_out = $attendance->check_out
            ? Carbon::parse($attendance->check_out)->format('H:i')
            : null;

        // 「break_start」「break_end」について「時間：分」の形式に変換
        foreach ($attendance->breaktimes as $breaktime) {
            $breaktime->break_start = $breaktime->break_start
                ? Carbon::parse($breaktime->break_start)->format('H:i')
                : null;

            $breaktime->break_end = $breaktime->break_end
                ? Carbon::parse($breaktime->break_end)->format('H:i')
                : null;
        }

        // breaktimesが空の場合、ビュー表示用にダミーデータを追加
        if ($attendance->breaktimes->isEmpty()) {
            $attendance->breaktimes = collect([
                (object)[
                    'id' => null, // 新規作成用
                    'break_start' => null,
                    'break_end' => null,
                ]
            ]);
        }

            // dateをCarbonインスタンスに変換
            $attendance->date = Carbon::parse($attendance->date);

            return view('attendance.detail', compact('attendance', 'user'));
        }

    // 「申請一覧」の表示
    public function listRequests(Request $request)
    {
                // ログインしていなければログインページにリダイレクト
        if (!auth()->check()) {
            return redirect()->route('admin.login');
        }

        // ログイン中のユーザーを取得
        $user = auth()->user();

        // 値が提供されていない場合、デフォルトでpending（承認待ち）とする
        $tab = $request->input('tab', 'pending');
        $currentTab = $tab;

        // 条件に応じてデータを取得
        // 管理者と一般ユーザーで異なる条件を設定
        $requests = AttendanceModification::with(['attendance.user']) // attendanceおよびattendanceに紐づいたuserも取得
            ->when($user->is_admin, function ($query) use ($tab) {
                // 管理者の場合
                $query->when($tab === 'pending', function ($query) {
                    $query->whereNull('approved_by'); // 承認待ち
                })
                ->when($tab === 'approved', function ($query) {
                    $query->whereNotNull('approved_by'); // 承認済み
                });
            }, function ($query) use ($tab, $user) {
                // 一般ユーザーの場合
                $query->where('staff_id', $user->id) // 自分の勤怠データのみ
                    ->when($tab === 'pending', function ($query) {
                        $query->whereNull('approved_by'); // 承認待ち
                    })
                    ->when($tab === 'approved', function ($query) {
                        $query->whereNotNull('approved_by'); // 承認済み
                    });
            })
            ->get();

        return view('attendance.request_list', compact('requests', 'currentTab'));
    }

}
