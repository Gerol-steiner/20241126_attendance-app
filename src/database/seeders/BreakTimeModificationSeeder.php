<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AttendanceModification;
use App\Models\BreakTimeModification;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class BreakTimeModificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. 全てのattendance_modificationsを取得
        $modifications = AttendanceModification::all();

        if ($modifications->isEmpty()) {
            $this->command->info('AttendanceModificationのデータが存在しません。');
            return;
        }

        // 2. 各attendance_modificationに対してbreaktime_modificationsを作成
        foreach ($modifications as $modification) {
            $hasSingleBreak = rand(0, 1) === 0; // 50%の確率で1つの休憩

            if ($hasSingleBreak) {
                BreakTimeModification::create([
                    'attendance_modification_id' => $modification->id,
                    'break_start' => '12:30:00',
                    'break_end' => '13:20:00',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                BreakTimeModification::create([
                    'attendance_modification_id' => $modification->id,
                    'break_start' => '12:30:00',
                    'break_end' => '13:10:00',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                BreakTimeModification::create([
                    'attendance_modification_id' => $modification->id,
                    'break_start' => '15:00:00',
                    'break_end' => '15:30:00',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('BreakTimeModificationSeeder: ダミーデータを作成しました。');

        // 3. 承認済みのattendance_modificationsを反映
        $approvedModifications = AttendanceModification::with('breakTimeModifications')
            ->whereNotNull('approved_by')
            ->get();

        if ($approvedModifications->isEmpty()) {
            $this->command->info('承認済みのAttendanceModificationのデータが存在しません。');
            return;
        }

        DB::transaction(function () use ($approvedModifications) {
            foreach ($approvedModifications as $modification) {
                if ($modification->attendance_id) {
                    // attendance_idが既存する場合、対象のattendanceを取得して上書き
                    $attendance = Attendance::find($modification->attendance_id);
                    if ($attendance) {
                        $attendance->update([
                            'check_in' => $modification->check_in,
                            'check_out' => $modification->check_out,
                        ]);

                        // 紐づくbreak_timesを削除して再作成
                        $attendance->breakTimes()->delete();
                    }
                } else {
                    // attendance_idがnullの場合、新しいattendanceレコードを作成
                    $attendance = Attendance::create([
                        'user_id' => $modification->staff_id,
                        'date' => $modification->date,
                        'check_in' => $modification->check_in,
                        'check_out' => $modification->check_out,
                    ]);

                    // 修正申請レコードに新しいattendance_idを紐付け
                    $modification->update(['attendance_id' => $attendance->id]);
                }

                // BreakTimeModificationから対応するbreak_timesを作成
                foreach ($modification->breakTimeModifications as $breaktimeModification) {
                    $attendance->breakTimes()->create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $breaktimeModification->break_start,
                        'break_end' => $breaktimeModification->break_end,
                    ]);
                }
            }
        });
    }
}
