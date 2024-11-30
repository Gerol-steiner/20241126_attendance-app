<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\BreakRecord;

class BreakRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Attendancesテーブルの全てのレコードを取得
        $attendances = Attendance::all();

        // ルール（１、２、３）を順番に割り当てる
        $pattern = 0; // ルールの切り替え用

        foreach ($attendances as $attendance) {
            switch ($pattern) {
                case 0: // パターン１: 1つの休憩
                    BreakRecord::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => '12:00:00',
                        'break_end' => '13:00:00',
                    ]);
                    break;

                case 1: // パターン２: 2つの休憩
                    BreakRecord::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => '12:00:00',
                        'break_end' => '12:30:00',
                    ]);
                    BreakRecord::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => '15:00:00',
                        'break_end' => '15:30:00',
                    ]);
                    break;

                case 2: // パターン３: 3つの休憩
                    BreakRecord::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => '10:00:00',
                        'break_end' => '10:15:00',
                    ]);
                    BreakRecord::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => '12:05:00',
                        'break_end' => '12:30:00',
                    ]);
                    BreakRecord::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => '15:00:00',
                        'break_end' => '15:20:00',
                    ]);
                    break;
            }

            // 次のルールに切り替える
            $pattern = ($pattern + 1) % 3;
        }
    }
}
