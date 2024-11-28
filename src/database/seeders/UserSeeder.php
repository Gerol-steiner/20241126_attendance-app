<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\DB;  // 追加
use Illuminate\Support\Facades\Hash; // パスワードのハッシュ化

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            // 管理者
            [
                'name' => '佐藤 剛士',
                'email' => 'satou@test',
                'password' => Hash::make('password'), // ダミーのパスワード
                'is_admin' => 1,
                'email_verified_at' => now(), // メール認証済み
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '鈴木 弘子',
                'email' => 'suzuki@test',
                'password' => Hash::make('password'), // ダミーのパスワード
                'is_admin' => 1,
                'email_verified_at' => now(), // メール認証済み
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // 一般ユーザー
            [
                'name' => '山田 太郎',
                'email' => 'yamada@test',
                'password' => Hash::make('password'), // ダミーのパスワード
                'is_admin' => 0,
                'email_verified_at' => now(), // メール認証済み
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '西 伶奈',
                'email' => 'nishi@test',
                'password' => Hash::make('password'), // ダミーのパスワード
                'is_admin' => 0,
                'email_verified_at' => now(), // メール認証済み
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '増田 一世',
                'email' => 'masuda@test',
                'password' => Hash::make('password'), // ダミーのパスワード
                'is_admin' => 0,
                'email_verified_at' => now(), // メール認証済み
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '山本 敬吉',
                'email' => 'yamamoto@test',
                'password' => Hash::make('password'), // ダミーのパスワード
                'is_admin' => 0,
                'email_verified_at' => now(), // メール認証済み
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '秋田 朋美',
                'email' => 'akita@test',
                'password' => Hash::make('password'), // ダミーのパスワード
                'is_admin' => 0,
                'email_verified_at' => now(), // メール認証済み
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '中西 教夫',
                'email' => 'nakanisi@test',
                'password' => Hash::make('password'), // ダミーのパスワード
                'is_admin' => 0,
                'email_verified_at' => now(), // メール認証済み
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
