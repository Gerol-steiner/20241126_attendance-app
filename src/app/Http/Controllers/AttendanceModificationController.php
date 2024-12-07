<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceModification;
use App\Models\BreakTimeModification;
use Carbon\Carbon;
use Auth;

class AttendanceModificationController extends Controller
{
    public function update(Request $request, $user_id)
    {
        // ユーザーが入力した年と月日を取得
        $dateYear = $request->input('date_year');
        $dateMonthDay = $request->input('date_month_day');

        // 月日を「n月j日」の形式から変換
        try {
            $date = Carbon::createFromFormat('Y-n月j日', $dateYear . '-' . $dateMonthDay)->format('Y-m-d');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '日付形式が無効です。');
        }

        // 1. 修正対象の勤怠レコードを取得（なければnull）
        $attendance = Attendance::where('user_id', $user_id)
            ->where('date', $date)
            ->first();

        // ログイン中のユーザーが管理者かどうかを判定
        $isAdmin = Auth::user()->is_admin;

        // 2. AttendanceModificationレコードを作成
        $attendanceModification = AttendanceModification::create([
            'attendance_id' => $attendance ? $attendance->id : null, // 該当するattendanceがない場合はnull
            'staff_id' => $user_id, // 勤怠詳細画面から渡された一般ユーザーのID
            'requested_by' => Auth::id(), // 修正を起案したユーザーのID
            'check_in' => $request->input('check_in'), // 出勤時刻
            'check_out' => $request->input('check_out'), // 退勤時刻
            'remark' => $request->input('remarks'), // 修正理由（備考）
            'date' => $date, // 修正対象の日付
            'approved_by' => $isAdmin ? Auth::id() : null, // 管理者なら即承認
        ]);

        // 3. BreakTimeModificationレコードを作成
        if ($request->has('breaktimes')) {
            foreach ($request->input('breaktimes') as $breaktime) {
                // 開始時刻または終了時刻のどちらかが設定されている場合にのみレコードを作成
                if (!empty($breaktime['start']) || !empty($breaktime['end'])) {
                    BreakTimeModification::create([
                        'attendance_modification_id' => $attendanceModification->id,
                        'break_start' => $breaktime['start'] ?? null,
                        'break_end' => $breaktime['end'] ?? null,
                    ]);
                }
            }
        }

        // 4. リダイレクト
        return redirect()->route('admin.attendance.daily_list')
            ->with('success', '勤怠修正が登録されました。');
    }

    // 修正申請の承認画面の表示
    public function showApprovalForm($attendance_correct_request)
    {
        // ログインしていなければログインページにリダイレクト
        if (!auth()->check()) {
            return redirect()->route('admin.login');
        }

        // 修正申請レコードを取得
        $modificationRequest = AttendanceModification::with(['staff', 'attendance', 'breakTimeModifications'])
            ->findOrFail($attendance_correct_request);

        // ビューにデータを渡して表示
        return view('admin.approve_request', compact('modificationRequest'));
    }

    // 管理者による修正申請の承認
    public function approveModification(Request $request, $attendance_correct_request)
    {
        // ログインしていなければログインページにリダイレクト
        if (!auth()->check()) {
            return redirect()->route('admin.login');
        }

        // 修正申請レコードを取得
        $modificationRequest = AttendanceModification::findOrFail($attendance_correct_request);

        // 承認処理（approved_by に現在のユーザーIDを格納）
        $modificationRequest->update([
            'approved_by' => auth()->id(),
        ]);

        // 承認済み後、同じページを再表示
        return redirect()->route('attendance_modification.approve', ['attendance_correct_request' => $attendance_correct_request])
            ->with('success', '修正申請を承認しました。');
    }
}
