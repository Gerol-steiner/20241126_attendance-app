<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * ダミーデータを作成する最大ユーザー数
     *
     * @var int
     */
    private $maxUsers = 3; // 生成するユーザー数を調整可能

    /**
     * シーダーを実行する
     */
    public function run()
    {
        // "is_admin" が 0（管理者ではない）のユーザーを取得
        // 取得する件数は $maxUsers で制限
        $users = User::where('is_admin', 0)->limit($this->maxUsers)->get();

        // ダミーデータを生成する日付範囲を設定
        $startDate = Carbon::create(2024, 10, 20); // 開始日
        $endDate = Carbon::create(2024, 11, 28);   // 終了日

        // 各ユーザーごとにデータを生成
        foreach ($users as $user) {
            // 日付範囲をリセット
            $dateRange = $startDate->copy();

            // 日付が終了日を超えるまで繰り返す
            while ($dateRange->lte($endDate)) {
                // 土曜日と日曜日をスキップ
                if ($dateRange->isWeekend()) {
                    $dateRange->addDay();
                    continue;
                }

                // ダミーデータをAttendanceテーブルに挿入
                Attendance::create([
                    'user_id' => $user->id, // ユーザーID
                    'date' => $dateRange->format('Y-m-d'), // 現在の日付
                    // 出勤時間（07:50～08:10の範囲でランダム）
                    'check_in' => Carbon::createFromTime(7, 50)
                        ->addMinutes(rand(0, 20))
                        ->format('H:i:s'),
                    // 退勤時間（17:50～18:30の範囲でランダム）
                    'check_out' => Carbon::createFromTime(17, 50)
                        ->addMinutes(rand(0, 40))
                        ->format('H:i:s'),
                    // 休憩開始時間（11:50～12:10の範囲でランダム）
                    'break_start' => Carbon::createFromTime(11, 50)
                        ->addMinutes(rand(0, 20))
                        ->format('H:i:s'),
                    // 休憩終了時間（12:50～13:10の範囲でランダム）
                    'break_end' => Carbon::createFromTime(12, 50)
                        ->addMinutes(rand(0, 20))
                        ->format('H:i:s'),
                    'remarks' => null, // 備考（デフォルトで null）
                ]);

                // 次の日に進む
                $dateRange->addDay();
            }
        }
    }
}
