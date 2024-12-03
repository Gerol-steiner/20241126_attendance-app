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

            $breakMinutes = 0;
            if ($attendance) {
                foreach ($attendance->breaktimes as $breaktime) {
                    if (!empty($breaktime->break_start) && !empty($breaktime->break_end)) {
                        $breakMinutes += Carbon::parse($breaktime->break_start)
                            ->diffInMinutes(Carbon::parse($breaktime->break_end));
                    }
                }
            }

            $breakTime = $breakMinutes ? sprintf('%d:%02d', floor($breakMinutes / 60), $breakMinutes % 60) : '-';

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
                    : '-',
                'check_out' => $attendance && $attendance->check_out
                    ? Carbon::parse($attendance->check_out)->format('H:i')
                    : '-',
                'breakTime' => $attendance ? $breakTime : '-',
                'totalTime' => $attendance ? ($totalTime ?? '-') : '-',
                'attendance_id' => $attendance ? $attendance->id : null,
];
        }

        return view('admin.attendance_daily_list', [
            'currentDate' => $currentDate,
            'isToday' => $isToday,
            'data' => $data,
        ]);
    }

}
