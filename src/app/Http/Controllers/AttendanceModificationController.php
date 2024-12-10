<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceModification;
use App\Models\BreakTimeModification;
use Carbon\Carbon;
use Auth;
use App\Http\Requests\AttendanceUpdateRequest;

class AttendanceModificationController extends Controller
{
    public function update(AttendanceUpdateRequest $request, $user_id)
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

        // ログイン中のユーザーが管理者かどうかを判定
        $isAdmin = Auth::user()->is_admin;

        // １．修正対象の勤怠レコードを取得または作成
        $attendance = Attendance::where('user_id', $user_id)
            ->where('date', $date)
            ->first();

        if ($isAdmin && !$attendance) {
            // 管理者ユーザーの場合、該当するattendanceレコードがなければ作成
            $attendance = Attendance::create([
                'user_id' => $user_id,
                'date' => $date,
            ]);
        }

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

        // ４．管理者ユーザーの場合、attendanceレコードを更新
        if ($isAdmin) {
            // 出勤・退勤時刻を更新
            $attendance->update([
                'check_in' => $attendanceModification->check_in,
                'check_out' => $attendanceModification->check_out,
            ]);

            // 紐づくbreak_timeレコードを削除
            $attendance->breakTimes()->delete();

            // break_time_modificationレコードを基にbreak_timeレコードを作成
            foreach ($attendanceModification->breakTimeModifications as $breaktimeModification) {
                $attendance->breakTimes()->create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $breaktimeModification->break_start,
                    'break_end' => $breaktimeModification->break_end,
                ]);
            }
        }

        // フラッシュメッセージとリダイレクト先の設定
        if ($isAdmin) {
            return redirect()->route('admin.attendance.daily_list')
                ->with('success', '勤怠修正が完了しました。');
        } else {
            return redirect()->route('attendance.list')
                ->with('success', '勤怠修正のリクエストが申請されました');
        }
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
        $modificationRequest = AttendanceModification::with('breakTimeModifications')->findOrFail($attendance_correct_request);

        // 承認処理（approved_by に現在のユーザーIDを格納）
        $modificationRequest->update([
            'approved_by' => auth()->id(),
        ]);

        // attendance_idの確認
        if ($modificationRequest->attendance_id) {
            // １．attendance_idに値がある場合
            $attendance = Attendance::findOrFail($modificationRequest->attendance_id);

            // 出勤・退勤時刻を上書き
            $attendance->update([
                'check_in' => $modificationRequest->check_in,
                'check_out' => $modificationRequest->check_out,
            ]);

            // 紐づくbreak_timeレコードを削除
            $attendance->breakTimes()->delete();

            // break_time_modificationレコードをもとにbreak_timeレコードを作成
            foreach ($modificationRequest->breakTimeModifications as $breaktimeModification) {
                $attendance->breakTimes()->create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $breaktimeModification->break_start,
                    'break_end' => $breaktimeModification->break_end,
                ]);
            }
        } else {
            // ２． attendance_idがnullの場合（新しいattendanceレコードを作成）
            $attendance = Attendance::create([
                'user_id' => $modificationRequest->staff_id,
                'date' => $modificationRequest->date,
                'check_in' => $modificationRequest->check_in,
                'check_out' => $modificationRequest->check_out,
            ]);

            // break_time_modificationレコードをもとにbreak_timeレコードを作成
            foreach ($modificationRequest->breakTimeModifications as $breaktimeModification) {
                $attendance->breakTimes()->create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $breaktimeModification->break_start,
                    'break_end' => $breaktimeModification->break_end,
                ]);
            }

            // 修正申請レコードに新しく作成したattendanceレコードのIDを格納
            $modificationRequest->update([
                'attendance_id' => $attendance->id,
            ]);
        }



        // 承認済み後、同じページを再表示
        return redirect()->route('attendance_modification.approve', ['attendance_correct_request' => $attendance_correct_request])
            ->with('success', '修正申請を承認しました。');
    }
}
