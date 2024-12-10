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
        $startDate = Carbon::now()->subDays(50); // 開始日
        $endDate = Carbon::now()->subDays(3);  // 終了日

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
                    'check_in' => '08:00:00', // 固定の出勤時間
                    'check_out' => '18:00:00', // 固定の退勤時間
                ]);

                // 次の日に進む
                $dateRange->addDay();
            }
        }
    }
}
