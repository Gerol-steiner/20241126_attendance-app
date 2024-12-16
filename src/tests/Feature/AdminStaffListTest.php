<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class AdminStaffListTest extends TestCase
{
    use RefreshDatabase;

    public function testDisplaysAllGeneralUsersInStaffList()
    {
        // 一般ユーザーを2名作成
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

        // 管理者ユーザーを作成してログイン
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);
        $this->actingAs($adminUser);

        // スタッフ一覧画面にアクセス
        $response = $this->get(route('admin.staff.list'));

        // 一般ユーザーの名前とメールアドレスが表示されていることを確認
        $response->assertSee('User One');
        $response->assertSee('userone@example.com');
        $response->assertSee('User Two');
        $response->assertSee('usertwo@example.com');

        dump('スタッフ一覧画面で全ての一般ユーザーの名前とメールアドレスが表示されていることを確認しました');
    }

    public function test_DisplaysMonthlyAttendanceCorrectlyForSpecificStaff()
    {
        // 一般ユーザーを作成
        $user = User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        // 勤怠レコードと休憩レコードを今月の1日〜5日分作成
        $dates = collect(range(1, 5))->map(function ($day) {
            return now()->startOfMonth()->addDays($day - 1);
        });

        foreach ($dates as $date) {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'date' => $date->toDateString(),
                'check_in' => '09:10:00',
                'check_out' => '18:00:00',
            ]);

            $attendance->breakTimes()->create([
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
            ]);
        }

        // 管理者ユーザーを作成しログイン
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);

        $this->actingAs($adminUser);

        // スタッフ一覧画面にアクセス
        $response = $this->get(route('admin.staff.list'));

        // スタッフ一覧の詳細リンクをクリックして月次勤怠画面に遷移
        $response = $this->get(route('admin.attendance.staff.monthly_list', ['id' => $user->id]));

        // 月次勤怠画面で5日分の勤怠情報を確認
        $dates->each(function ($date) use ($response) {
            $response->assertSee($date->format('m/d'));
        });

        // 出勤時間、退勤時間、休憩時間、勤務合計時間がそれぞれ5回表示されていることを確認
        $this->assertEquals(5, substr_count($response->getContent(), '09:10'));
        $this->assertEquals(5, substr_count($response->getContent(), '18:00'));
        $this->assertEquals(5, substr_count($response->getContent(), '1:00')); // 休憩時間
        $this->assertEquals(5, substr_count($response->getContent(), '7:50')); // 合計勤務時間

        $response->assertSee('<a href="' . route('attendance.detail', ['id' => Attendance::first()->id]) . '">詳細</a>', false);

        dump('管理者用月次勤怠一覧画面で当月の正しい勤怠情報が表示されることを確認しました');
    }

    public function test_DisplaysPreviousMonthAttendanceCorrectlyForSpecificStaff()
    {
        // 一般ユーザーを作成
        $user = User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        // 勤怠レコードと休憩レコードを先月の1日〜5日分作成
        $dates = collect(range(1, 5))->map(function ($day) {
            return now()->subMonth()->startOfMonth()->addDays($day - 1);
        });

        foreach ($dates as $date) {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'date' => $date->toDateString(),
                'check_in' => '09:10:00',
                'check_out' => '18:00:00',
            ]);

            $attendance->breakTimes()->create([
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
            ]);
        }

        // 管理者ユーザーを作成しログイン
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);

        $this->actingAs($adminUser);

        // スタッフ一覧画面にアクセス
        $response = $this->get(route('admin.staff.list'));

        // スタッフ一覧の詳細リンクをクリックして月次勤怠画面に遷移
        $response = $this->get(route('admin.attendance.staff.monthly_list', ['id' => $user->id]));

        // 月次勤怠画面の「前月」リンクを押下
        $response = $this->get(route('admin.attendance.staff.monthly_list', [
            'id' => $user->id,
            'month' => now()->subMonth()->format('Y-m')
        ]));

        // 月次勤怠画面で5日分の勤怠情報を確認
        $dates->each(function ($date) use ($response) {
            $response->assertSee($date->format('m/d'));
        });

        // 出勤時間、退勤時間、休憩時間、勤務合計時間がそれぞれ5回表示されていることを確認
        $this->assertEquals(5, substr_count($response->getContent(), '09:10'));
        $this->assertEquals(5, substr_count($response->getContent(), '18:00'));
        $this->assertEquals(5, substr_count($response->getContent(), '1:00')); // 休憩時間
        $this->assertEquals(5, substr_count($response->getContent(), '7:50')); // 合計勤務時間

        $response->assertSee('<a href="' . route('attendance.detail', ['id' => Attendance::first()->id]) . '">詳細</a>', false);

        dump('管理者用月次勤怠一覧画面にて、前月の正しい勤怠情報が表示されていることを確認しました');
    }

    public function test_DisplaysNextMonthAttendanceCorrectlyForSpecificStaff()
    {
        // 一般ユーザーを作成
        $user = User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        // 勤怠レコードと休憩レコードを翌月の1日〜5日分作成
        $dates = collect(range(1, 5))->map(function ($day) {
            return now()->addMonth()->startOfMonth()->addDays($day - 1);
        });

        foreach ($dates as $date) {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'date' => $date->toDateString(),
                'check_in' => '09:10:00',
                'check_out' => '18:00:00',
            ]);

            $attendance->breakTimes()->create([
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
            ]);
        }

        // 管理者ユーザーを作成しログイン
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);

        $this->actingAs($adminUser);

        // スタッフ一覧画面にアクセス
        $response = $this->get(route('admin.staff.list'));

        // スタッフ一覧の詳細リンクをクリックして月次勤怠画面に遷移
        $response = $this->get(route('admin.attendance.staff.monthly_list', ['id' => $user->id]));

        // 月次勤怠画面の「翌月」リンクを押下
        $response = $this->get(route('admin.attendance.staff.monthly_list', [
            'id' => $user->id,
            'month' => now()->addMonth()->format('Y-m')
        ]));

        // 月次勤怠画面で5日分の勤怠情報を確認
        $dates->each(function ($date) use ($response) {
            $response->assertSee($date->format('m/d'));
        });

        // 出勤時間、退勤時間、休憩時間、勤務合計時間がそれぞれ5回表示されていることを確認
        $this->assertEquals(5, substr_count($response->getContent(), '09:10'));
        $this->assertEquals(5, substr_count($response->getContent(), '18:00'));
        $this->assertEquals(5, substr_count($response->getContent(), '1:00')); // 休憩時間
        $this->assertEquals(5, substr_count($response->getContent(), '7:50')); // 合計勤務時間

        $response->assertSee('<a href="' . route('attendance.detail', ['id' => Attendance::first()->id]) . '">詳細</a>', false);

        dump('管理者用月次勤怠一覧画面にて、翌月の正しい勤怠情報が表示されていることを確認しました');
    }

    public function test_DisplaysAttendanceDetailCorrectlyForAdmin()
    {
        // 一般ユーザーを作成
        $user = User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        // 本日の勤怠レコードと休憩レコードを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
        ]);

        $attendance->breakTimes()->create([
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        // 管理者ユーザーを作成しログイン
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);

        $this->actingAs($adminUser);

        // スタッフ一覧画面にアクセス
        $this->get(route('admin.staff.list'));

        // スタッフ一覧の詳細リンクをクリックして月次勤怠画面に遷移
        $this->get(route('admin.attendance.staff.monthly_list', ['id' => $user->id]));

        // 本日の勤怠詳細画面に遷移
        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));

        // 勤怠詳細画面で情報を確認
        $response->assertSee($user->name);
        $response->assertSee('<input type="number" id="date_year" name="date_year" value="' . now()->format('Y') . '" readonly>', false);
        $response->assertSee('<input type="text" id="date_month_day" name="date_month_day" value="' . now()->format('n月j日') . '">', false);
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);

        dump('勤怠詳細画面に正しい情報が表示されていることを確認しました');
    }
}
