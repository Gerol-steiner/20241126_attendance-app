<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class UserAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

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

        // 勤怠詳細画面にGETリクエストを送信
        $response = $this->get(route('attendance.detail', $attendance->id));

        // ユーザー名がログイン中のユーザー名となっていることを確認
        $response->assertSee('General User');
        dump('勤怠詳細ページの名前欄にログインユーザーの名前が表示されていることを確認しました');

        // 日付が勤怠レコードと同じ形式で表示されていることを確認 (年、月、日)
        $response->assertSee(now()->format('Y'));  // 年
        $response->assertSee(now()->format('n月j日'));  // 月日
        dump('勤怠詳細ページの日付欄に選択された勤怠の作成された年、月、日が正しく表示されていることを確認しました');

        // 出勤と退勤時刻が勤怠レコードと同じであることを確認
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        dump('勤怠詳細ページの出退勤欄に、ユーザーの打刻時間と同じ時刻が表示されていることを確認しました');

        // 休憩時間が勤怠レコードと同じであることを確認
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        dump('勤怠詳細ページの休憩欄に、ユーザーの打刻時間と同じ時刻が表示されていることを確認しました');

        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);
    }

}
