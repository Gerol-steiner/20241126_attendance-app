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

        // 1. 修正対象の勤怠レコードを取得（なかれば作成）
        $attendance = Attendance::firstOrCreate(
            [
                'user_id' => $user_id,
                'date' => $date,
            ],
            [
                'check_in' => null,
                'check_out' => null,
            ]
        );

        // 2. AttendanceModificationレコードを作成
        $attendanceModification = AttendanceModification::create([
            'attendance_id' => $attendance->id,
            'user_id' => Auth::id(),
            'check_in' => $request->input('check_in'),
            'check_out' => $request->input('check_out'),
            'remark' => $request->input('remarks'),
        ]);

        // 3. BreakTimeModificationレコードを作成
        if ($request->has('breaktimes')) {
            foreach ($request->input('breaktimes') as $breaktime) {
                BreakTimeModification::create([
                    'attendance_modification_id' => $attendanceModification->id,
                    'break_start' => $breaktime['start'] ?? null,
                    'break_end' => $breaktime['end'] ?? null,
                ]);
            }
        }

        // 4. リダイレクト
        return redirect()->route('admin.attendance.daily_list')
            ->with('success', '勤怠修正が登録されました。');
    }
}
