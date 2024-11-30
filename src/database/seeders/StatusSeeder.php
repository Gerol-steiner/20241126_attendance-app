<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // 追加

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('statuses')->insert([
            [
                'name' => '勤務外',
                'description' => '本日の勤務をまだ開始していない状態',
            ],
            [
                'name' => '出勤中',
                'description' => '勤務が開始されている状態',
            ],
            [
                'name' => '休憩中',
                'description' => '勤務中に休憩を取っている状態',
            ],
            [
                'name' => '退勤済',
                'description' => '本日の勤務が終了した状態',
            ],
        ]);
    }
}
