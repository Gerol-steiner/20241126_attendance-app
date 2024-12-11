<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AttendanceController;

class AttendanceScreenTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_status_as_outside_work_when_no_attendance_record_exists()
    {
        // ステータスデータを手動で挿入
        DB::table('statuses')->insert([
            ['id' => 1, 'name' => '勤務外'],
            ['id' => 2, 'name' => '出勤中'],
            ['id' => 3, 'name' => '休憩中'],
            ['id' => 4, 'name' => '退勤済'],
        ]);

        // 一般ユーザーを作成してログイン
        $user = User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0, // 一般ユーザー
        ]);

        $this->actingAs($user);

        // 勤怠画面にGETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ステータスが「勤務外」であることを確認
        $response->assertSee('勤務外');

        // ページが正常にロードされていることを確認
        $response->assertStatus(200);

        dump('勤怠画面に"勤務外"が表示されていることを確認しました');
    }

    /** @test */
    public function it_displays_status_as_working_after_checking_in()
    {
        // ステータスデータを手動で挿入
        DB::table('statuses')->insert([
            ['id' => 1, 'name' => '勤務外'],
            ['id' => 2, 'name' => '出勤中'],
            ['id' => 3, 'name' => '休憩中'],
            ['id' => 4, 'name' => '退勤済'],
        ]);

        // 一般ユーザーを作成してログイン
        $user = User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0, // 一般ユーザー
        ]);

        $this->actingAs($user);

        // 出勤ボタンを押下
        $response = $this->post(route('attendance.checkIn'));

        // 勤怠画面を表示
        $response = $this->get(route('attendance.index'));

        // ステータスが「出勤中」となっていることを確認
        $response->assertSee('出勤中');

        // ページが正常にロードされていることを確認
        $response->assertStatus(200);

        dump('勤怠画面に"出勤中"が表示されていることを確認しました');
    }

    /** @test */
    public function it_displays_status_as_breaking_after_starting_break()
    {
        // ステータスデータを手動で挿入
        DB::table('statuses')->insert([
            ['id' => 1, 'name' => '勤務外'],
            ['id' => 2, 'name' => '出勤中'],
            ['id' => 3, 'name' => '休憩中'],
            ['id' => 4, 'name' => '退勤済'],
        ]);

        // 一般ユーザーを作成してログイン
        $user = User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        $this->actingAs($user);

        // 出勤処理
        $this->post(route('attendance.checkIn'));

        // 休憩入処理
        $this->post(route('attendance.startBreak'));

        // 勤怠画面にGETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ビューに「休憩中」が表示されていることを確認
        $response->assertSee('休憩中');

        dump('勤怠画面に"休憩中"が表示されていることを確認しました');
    }

    /** @test */
    public function it_displays_status_as_finished_after_checking_out()
    {
        // ステータスデータを手動で挿入
        DB::table('statuses')->insert([
            ['id' => 1, 'name' => '勤務外'],
            ['id' => 2, 'name' => '出勤中'],
            ['id' => 3, 'name' => '休憩中'],
            ['id' => 4, 'name' => '退勤済'],
        ]);

        // 一般ユーザーを作成してログイン
        $user = User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        $this->actingAs($user);

        // 勤怠画面にアクセス（初期状態確認）
        $this->get(route('attendance.index'))->assertSee('勤務外');

        // 出勤処理
        $this->post(route('attendance.checkIn'));

        // 退勤処理
        $this->post(route('attendance.checkOut'));

        // 勤怠画面にGETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ビューに「退勤済」が表示されていることを確認
        $response->assertSee('退勤済');

        dump('勤怠画面に"退勤済"が表示されていることを確認しました');
    }

}
