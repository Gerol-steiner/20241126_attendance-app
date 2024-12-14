<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class MonthlyAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_all_attendance_records_on_monthly_list()
    {
        // 一般ユーザーを作成
        $user = User::create([
            'name' => 'General User',
            'email' => 'generaluser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        $this->actingAs($user);

        // 現在の月の1日から月末までの日付範囲を定義
        $dateRange = Carbon::now()->startOfMonth()->daysUntil(Carbon::now()->endOfMonth());

        // 勤怠レコードと休憩時間を作成
        foreach ($dateRange as $date) {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'date' => $date->toDateString(),
                'check_in' => '09:00:00',
                'check_out' => '18:00:00',
            ]);

            Breaktime::create([
                'attendance_id' => $attendance->id,
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
            ]);
        }

        // 月次勤怠一覧画面にGETリクエストを送信
        $response = $this->get(route('attendance.list'));

        // 勤怠データが全て表示されていることを確認
        foreach ($dateRange as $date) {
            $response->assertSee($date->format('m/d')); // 日付
            $response->assertSee('09:00');             // 出勤時刻
            $response->assertSee('18:00');             // 退勤時刻
            $response->assertSee('1:00');              // 休憩時間
            $response->assertSee('8:00');              // 合計勤務時間 (09:00 - 18:00 - 1:00 の計算結果)

            // 詳細リンクの存在確認
            $attendance = Attendance::where('date', $date->format('Y-m-d'))->first();
            if ($attendance) {
                $response->assertSee('<a href="' . route('attendance.detail', $attendance->id) . '">詳細</a>', false);
            }
        }


        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        dump('今月の勤怠データが月次勤怠一覧画面に正確に表示されていることを確認しました');
    }

    /** @test */
    public function it_displays_current_year_and_month_on_monthly_attendance_list()
    {
        // 一般ユーザーを作成してログイン
        $user = User::create([
            'name' => 'General User',
            'email' => 'generaluser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        $this->actingAs($user);

        // 月次勤怠一覧画面にGETリクエストを送信
        $response = $this->get(route('attendance.list'));

        // 現在の年月を全角で取得（例：２０２４/１２）
        $currentYearMonth = mb_convert_kana(now()->format('Y/m'), 'N');

        // 画面に現在の年月が表示されていることを確認
        $response->assertSee($currentYearMonth);

        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        dump('月次勤怠一覧画面に現在の年月が表示されていることを確認しました');
    }

    /** @test */
    public function it_displays_previous_month_attendance_records_on_monthly_list()
    {
        // 一般ユーザーを作成
        $user = User::create([
            'name' => 'General User',
            'email' => 'generaluser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        $this->actingAs($user);

        // 先月の1日から月末までの日付範囲を定義
        $dateRange = Carbon::now()->subMonth()->startOfMonth()->daysUntil(Carbon::now()->subMonth()->endOfMonth());

        // 勤怠レコードと休憩時間を作成
        foreach ($dateRange as $date) {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'date' => $date->toDateString(),
                'check_in' => '09:00:00',
                'check_out' => '18:00:00',
            ]);

            Breaktime::create([
                'attendance_id' => $attendance->id,
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
            ]);
        }

        // 月次勤怠一覧画面にGETリクエストを送信
        $response = $this->get(route('attendance.list'));

        // 「前月」リンクを押下（前月の勤怠一覧を表示）
        $previousMonth = Carbon::now()->subMonth()->format('Y-m');
        $response = $this->get(route('attendance.list', ['month' => $previousMonth]));

        // 勤怠データが全て表示されていることを確認
        foreach ($dateRange as $date) {
            $response->assertSee($date->format('m/d')); // 日付
            $response->assertSee('09:00');             // 出勤時刻
            $response->assertSee('18:00');             // 退勤時刻
            $response->assertSee('1:00');              // 休憩時間
            $response->assertSee('8:00');              // 合計勤務時間 (09:00 - 18:00 - 1:00 の計算結果)

            // 詳細リンクの存在確認
            $attendance = Attendance::where('date', $date->format('Y-m-d'))->first();
            if ($attendance) {
                $response->assertSee('<a href="' . route('attendance.detail', $attendance->id) . '">詳細</a>', false);
            }
        }

        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        dump('先月の勤怠データが月次勤怠一覧画面に正確に表示されていることを確認しました');
    }

    /** @test */
    public function it_displays_next_month_attendance_records_on_monthly_list()
    {
        // 一般ユーザーを作成
        $user = User::create([
            'name' => 'General User',
            'email' => 'generaluser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        $this->actingAs($user);

        // 翌月の1日から月末までの日付範囲を定義
        $dateRange = Carbon::now()->addMonth()->startOfMonth()->daysUntil(Carbon::now()->addMonth()->endOfMonth());

        // 勤怠レコードと休憩時間を作成
        foreach ($dateRange as $date) {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'date' => $date->toDateString(),
                'check_in' => '09:00:00',
                'check_out' => '18:00:00',
            ]);

            Breaktime::create([
                'attendance_id' => $attendance->id,
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
            ]);
        }

        // 月次勤怠一覧画面にGETリクエストを送信
        $response = $this->get(route('attendance.list'));

        // 「翌月」のリンクを押下
        $nextMonth = Carbon::now()->addMonth()->format('Y-m');
        $response = $this->get(route('attendance.list', ['month' => $nextMonth]));

        // 勤怠データが全て表示されていることを確認
        foreach ($dateRange as $date) {
            $response->assertSee($date->format('m/d')); // 日付
            $response->assertSee('09:00');             // 出勤時刻
            $response->assertSee('18:00');             // 退勤時刻
            $response->assertSee('1:00');              // 休憩時間
            $response->assertSee('8:00');              // 合計勤務時間 (09:00 - 18:00 - 1:00 の計算結果)

            // 詳細リンクの存在確認
            $attendance = Attendance::where('date', $date->format('Y-m-d'))->first();
            if ($attendance) {
                $response->assertSee('<a href="' . route('attendance.detail', $attendance->id) . '">詳細</a>', false);
            }
        }

        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        dump('翌月の勤怠データが月次勤怠一覧画面に正確に表示されていることを確認しました');
    }

    /** @test */
    public function it_displays_daily_attendance_details_correctly()
    {
        // 一般ユーザーを作成
        $user = User::create([
            'name' => 'General User',
            'email' => 'generaluser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        $this->actingAs($user);

        // 本日の勤怠レコードを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
        ]);

        // 本日の休憩時間レコードを作成
        Breaktime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        // 月次勤怠一覧画面にGETリクエストを送信
        $response = $this->get(route('attendance.list'));

        // 詳細リンクをクリックして勤怠詳細画面に遷移
        $response = $this->get(route('attendance.detail', $attendance->id));

        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        dump('月次勤怠一覧の詳細リンクから、該当の勤怠詳細画面が正常に読み込まれたことを確認しました');
    }

}
