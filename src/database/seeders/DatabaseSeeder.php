<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // DBファサードをインポート
use Illuminate\Support\Facades\Schema;  // 追記

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // 外部キー制約を解除
        Schema::disableForeignKeyConstraints();

        // シーディング前に全レコードを削除
        DB::table('users')->truncate();
        DB::table('attendances')->truncate();
        DB::table('statuses')->truncate();
        DB::table('status_changes')->truncate();
        DB::table('break_times')->truncate();
        DB::table('attendance_modifications')->truncate();
        DB::table('break_time_modifications')->truncate();

        // 外部キー制約を有効化
        Schema::enableForeignKeyConstraints();

        // ダミーデータ作成
        $this->call(UserSeeder::class);
        $this->call(AttendanceSeeder::class);
        $this->call(StatusSeeder::class);
        $this->call(BreakTimeSeeder::class);
        $this->call(AttendanceModificationSeeder::class);
        $this->call(BreakTimeModificationSeeder::class);
    }
}
