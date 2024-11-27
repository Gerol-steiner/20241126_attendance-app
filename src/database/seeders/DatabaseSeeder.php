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
        DB::table('attendance_statuses')->truncate();

        // 外部キー制約を有効化
        Schema::enableForeignKeyConstraints();

        // ダミーデータ作成
        $this->call(AttendanceStatusSeeder::class);
    }
}
