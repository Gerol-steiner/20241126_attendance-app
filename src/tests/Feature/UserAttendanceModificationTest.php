<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class UserAttendanceModificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_shows_validation_error_when_check_in_is_after_check_out()
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

        // 勤怠詳細画面にGETリクエストを送信
        $response = $this->get(route('attendance.detail', $attendance->id));

        // 勤怠詳細画面が正常に表示されていることを確認
        $response->assertStatus(200);

        // 勤怠詳細画面で不正なデータを送信
        $response = $this->post(route('attendance.update', ['user_id' => $user->id]), [
            '_token' => csrf_token(),
            'date_year' => now()->format('Y'),
            'date_month_day' => now()->format('n月j日'),
            'check_in' => '18:00', // 出勤時間が退勤時間より後
            'check_out' => '08:00', // 不適切な退勤時間
            'breaktimes' => [
                [
                    'start' => '12:00',
                    'end' => '13:00',
                ],
            ],
        ]);

        // セッションにバリデーションエラーが含まれることを確認
        $response->assertSessionHasErrors(['check_out']);

        // エラーメッセージが正しいことを確認
        $this->assertEquals(
            '出勤時間もしくは退勤時間が不適切な値です',
            session('errors')->first('check_out')
        );

        dump('勤怠詳細ページで不適切な出勤・退勤時間のバリデーションエラーが正しく表示されることを確認しました');
    }

    /** @test */
    public function it_shows_error_when_break_start_is_after_check_out()
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

        // 勤怠詳細画面で不正なデータを送信
        $response = $this->post(route('attendance.update', ['user_id' => $user->id]), [
            '_token' => csrf_token(),
            'date_year' => now()->format('Y'),
            'date_month_day' => now()->format('n月j日'),
            'check_in' => '09:00', // 正常な出勤時間
            'check_out' => '18:00', // 正常な退勤時間
            'breaktimes' => [
                [
                    'start' => '19:00', // 不正な休憩開始時間（退勤後）
                    'end' => '20:00', // 休憩終了時間
                ],
            ],
        ]);

        // セッションにバリデーションエラーが含まれることを確認
        $response->assertSessionHasErrors(['breaktimes.*.start']);

        // エラーメッセージが正しいことを確認
        $this->assertEquals(
            '休憩時間が勤務時間外です',
            session('errors')->first('breaktimes.*.start')
        );

        dump('休憩開始時間が退勤時間より後の場合に、適切なエラーメッセージが表示されることを確認しました');
    }

    /** @test */
    public function it_shows_error_when_break_end_is_after_check_out()
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

        // 勤怠詳細画面で不正なデータを送信
        $response = $this->post(route('attendance.update', ['user_id' => $user->id]), [
            '_token' => csrf_token(),
            'date_year' => now()->format('Y'),
            'date_month_day' => now()->format('n月j日'),
            'check_in' => '09:00', // 正常な出勤時間
            'check_out' => '18:00', // 正常な退勤時間
            'breaktimes' => [
                [
                    'start' => '12:00', // 正常な休憩開始時間
                    'end' => '19:00',   // 不正な休憩終了時間（退勤後）
                ],
            ],
        ]);

        // セッションにバリデーションエラーが含まれることを確認
        $response->assertSessionHasErrors(['breaktimes.*.end']);

        // エラーメッセージが正しいことを確認
        $this->assertEquals(
            '休憩時間が勤務時間外です',
            session('errors')->first('breaktimes.*.end')
        );

        dump('休憩終了時間が退勤時間より後の場合に、適切なエラーメッセージが表示されることを確認しました');
    }

    /** @test */
    public function it_shows_error_when_remarks_is_empty()
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

        // 勤怠詳細画面で備考欄を空白にして修正データを送信
        $response = $this->post(route('attendance.update', ['user_id' => $user->id]), [
            '_token' => csrf_token(),
            'date_year' => now()->format('Y'),
            'date_month_day' => now()->format('n月j日'),
            'check_in' => '09:00', // 正しい出勤時間
            'check_out' => '18:00', // 正しい退勤時間
            'breaktimes' => [
                [
                    'start' => '12:00', // 正しい休憩開始時間
                    'end' => '13:00',   // 正しい休憩終了時間
                ],
            ],
            'remarks' => '', // 備考欄を空白
        ]);

        // セッションにバリデーションエラーが含まれることを確認
        $response->assertSessionHasErrors(['remarks']);

        // エラーメッセージが正しいことを確認
        $this->assertEquals(
            '備考を記入してください',
            session('errors')->first('remarks')
        );

        dump('備考欄が空白の場合、適切なエラーメッセージが表示されることを確認しました');
    }

}
