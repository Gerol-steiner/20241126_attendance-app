<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class AdminDailyAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_daily_attendance_correctly_for_admin()
    {
        // 管理者ユーザーを作成しログイン
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'adminuser@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);

        $this->actingAs($adminUser);

        // 一般ユーザーを2人作成
        $user1 = User::create([
            'name' => 'User One',
            'email' => 'userone@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        $user2 = User::create([
            'name' => 'User Two',
            'email' => 'usertwo@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        // 昨日、当日、翌日の勤怠データと休憩データを作成
        $dates = [
            now()->subDay()->toDateString(),
            now()->toDateString(),
            now()->addDay()->toDateString(),
        ];

        // ダミーデータの全てをユニークにする
        $attendanceIds = []; // 生成された Attendance の ID を保存する配列

        foreach ($dates as $index => $date) {
            // User One の Attendance データ作成
            $attendance1 = Attendance::create([
                'user_id' => $user1->id,
                'date' => $date,
                'check_in' => sprintf('09:%02d:00', $index * 10), // 09:00, 09:10, 09:20
                'check_out' => sprintf('17:%02d:00', $index * 10), // 17:00, 17:10, 17:20
            ]);
            $attendance1->breakTimes()->create([
                'break_start' => sprintf('12:%02d:00', $index * 10), // 12:00, 12:10, 12:20
                'break_end' => sprintf('12:%02d:00', $index * 10 + 30), // 12:30, 12:40, 12:50
            ]);
            $attendanceIds['user1'][] = $attendance1->id;

            // User Two の Attendance データ作成
            $attendance2 = Attendance::create([
                'user_id' => $user2->id,
                'date' => $date,
                'check_in' => sprintf('08:%02d:00', $index * 15), // 08:00, 08:15, 08:30
                'check_out' => sprintf('16:%02d:00', $index * 15), // 16:00, 16:15, 16:30
            ]);
            $attendance2->breakTimes()->create([
                'break_start' => sprintf('13:%02d:00', $index * 15), // 13:00, 13:15, 13:30
                'break_end' => sprintf('13:%02d:00', $index * 15 + 20), // 13:20, 13:35, 13:50
            ]);
            $attendanceIds['user2'][] = $attendance2->id;
        }

        // 管理者用日次勤怠一覧画面の当日データを確認
        $response = $this->get(route('admin.attendance.daily_list', ['date' => now()->toDateString()]));
        $response->assertSee(now()->format('Y年m月d日'), false);
        $response->assertSee(now()->format('Y/m/d'));
        $response->assertSee('User One');
        $response->assertSee('09:10');
        $response->assertSee('17:10');
        $response->assertSee('0:30'); // 休憩時間
        $response->assertSee('7:30'); // 合計時間
        $response->assertSee('<a href="' . route('attendance.detail', $attendanceIds['user1'][1]) . '">詳細</a>', false);

        $response->assertSee('User Two');
        $response->assertSee('08:15');
        $response->assertSee('16:15');
        $response->assertSee('0:20'); // 休憩時間
        $response->assertSee('7:40'); // 合計時間
        $response->assertSee('<a href="' . route('attendance.detail', $attendanceIds['user2'][1]) . '">詳細</a>', false);

        // 前日リンクの動作を確認
        $response = $this->get(route('admin.attendance.daily_list', ['date' => now()->subDay()->toDateString()]));
        $response->assertSee(now()->subDay()->format('Y年n月j日'), false);
        $response->assertSee(now()->subDay()->format('Y/m/d'));
        $response->assertSee('User One');
        $response->assertSee('09:00'); // 前日のデータ
        $response->assertSee('17:00');
        $response->assertSee('0:30'); // 前日の休憩時間
        $response->assertSee('7:30'); // 前日の勤務合計時間
        $response->assertSee('<a href="' . route('attendance.detail', $attendanceIds['user1'][0]) . '">詳細</a>', false);

        $response->assertSee('User Two');
        $response->assertSee('08:00'); // 前日のデータ
        $response->assertSee('16:00');
        $response->assertSee('0:20'); // 前日の休憩時間
        $response->assertSee('7:40'); // 前日の勤務合計時間
        $response->assertSee('<a href="' . route('attendance.detail', $attendanceIds['user2'][0]) . '">詳細</a>', false);

        // 翌日リンクの動作を確認
        $response = $this->get(route('admin.attendance.daily_list', ['date' => now()->addDay()->toDateString()]));
        $response->assertSee(now()->addDay()->format('Y年n月j日'), false);
        $response->assertSee(now()->addDay()->format('Y/m/d'));
        $response->assertSee('User One');
        $response->assertSee('09:20'); // 翌日のデータ
        $response->assertSee('17:20');
        $response->assertSee('0:30'); // 翌日の休憩時間
        $response->assertSee('7:30'); // 翌日の勤務合計時間
        $response->assertSee('<a href="' . route('attendance.detail', $attendanceIds['user1'][2]) . '">詳細</a>', false);

        $response->assertSee('User Two');
        $response->assertSee('08:30'); // 翌日のデータ
        $response->assertSee('16:30');
        $response->assertSee('0:20'); // 翌日の休憩時間
        $response->assertSee('7:40'); // 翌日の勤務合計時間
        $response->assertSee('<a href="' . route('attendance.detail', $attendanceIds['user2'][2]) . '">詳細</a>', false);


        // 確認
        dump('管理者用の日次勤怠一覧に、前日・当日・翌日の日付と全ユーザーの勤怠情報が表示されていることを確認しました');
    }

}
