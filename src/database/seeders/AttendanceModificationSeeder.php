<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceModification;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AttendanceModificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // ダミーデータ設定
        $modificationsPerUser = 4; // 各ユーザーの勤怠データから作成する修正申請数
        $approvedCount = 2; // 承認済みにする件数（残りは承認待ち）

        // 1. 管理者ユーザー（is_admin=1）の最初の1人を取得
        $adminUser = User::where('is_admin', 1)->first();

        if (!$adminUser) {
            $this->command->info('管理者ユーザーが存在しません。');
            return;
        }

        // 2. Attendanceテーブルにレコードを持つ一般ユーザー（is_admin=0）を取得
        $users = User::where('is_admin', 0)
            ->whereHas('attendances')
            ->get();

        if ($users->isEmpty()) {
            $this->command->info('勤怠データを持つ一般ユーザーが存在しません。');
            return;
        }

        // 3. 各一般ユーザーの勤怠データからランダムに選択して作成
        foreach ($users as $user) {
            // 平日のattendanceレコードを取得
            $attendances = $user->attendances()
                ->whereRaw('WEEKDAY(date) BETWEEN 0 AND 4') // 平日のみ（0:月曜日 ～ 4:金曜日）
                ->inRandomOrder()
                ->limit($modificationsPerUser) // $modificationsPerUserの値を使用
                ->get();

            if ($attendances->isEmpty()) {
                continue;
            }

            // 選択した勤怠データに基づいてattendance_modificationレコードを作成
            foreach ($attendances as $index => $attendance) {
                AttendanceModification::create([
                    'attendance_id' => $attendance->id,
                    'staff_id' => $attendance->user_id,
                    'requested_by' => $attendance->user_id,
                    'approved_by' => $index < $approvedCount ? null : $adminUser->id, // $approvedCountの値を使用
                    'date' => $attendance->date,
                    'check_in' => '07:00:00',
                    'check_out' => '19:00:00',
                    'remark' => Str::random(15), // 15文字のランダムな文字列
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
