<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_DisplaysAttendanceDetailCorrectlyForAdmin()
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

        // 一般ユーザーを作成
        $user = User::create([
            'name' => 'General User',
            'email' => 'generaluser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        // 今日の勤怠レコードと休憩レコードを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
        ]);

        $attendance->breakTimes()->create([
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        // 日次勤怠一覧画面にアクセス
        $response = $this->get(route('admin.attendance.daily_list', ['date' => now()->toDateString()]));

        // 詳細リンクをクリックして勤怠詳細画面を表示
        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));

        // 勤怠詳細画面が正しく表示されていることを確認
        $response->assertSee($user->name); // 名前
        $response->assertSee('<input type="number" id="date_year" name="date_year" value="' . now()->format('Y') . '" readonly>', false); // 年
        $response->assertSee('<input type="text" id="date_month_day" name="date_month_day" value="' . now()->format('n月j日') . '">', false); // 月日
        $response->assertSee('value="09:00"', false); // 出勤時間
        $response->assertSee('value="17:00"', false); // 退勤時間
        $response->assertSee('value="12:00"', false); // 休憩開始時間
        $response->assertSee('value="13:00"', false); // 休憩終了時間

        // 確認
        dump('管理者が勤怠詳細画面にアクセスし、正しい勤怠情報が表示されることを確認しました');
    }

    public function test_DisplaysErrorForInvalidAttendanceTimes()
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

        // 一般ユーザーを作成
        $user = User::create([
            'name' => 'General User',
            'email' => 'generaluser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        // 今日の勤怠レコードと休憩レコードを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
        ]);

        $attendance->breakTimes()->create([
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        // 日次勤怠一覧画面にアクセス
        $response = $this->get(route('admin.attendance.daily_list', ['date' => now()->toDateString()]));

        // 詳細リンクをクリックして勤怠詳細画面を表示
        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));

        // 勤怠詳細画面にて出勤時間を退勤時間より後に設定
        $response = $this->post(route('attendance.update', ['user_id' => $user->id]), [
            'date_year' => now()->format('Y'),
            'date_month_day' => now()->format('n月j日'),
            'check_in' => '18:00', // 出勤時間を退勤時間より後に設定
            'check_out' => '08:00',
            'breaktimes' => [
                ['start' => '12:00', 'end' => '13:00']
            ],
            'remarks' => '時間修正テスト',
        ]);

        // エラーメッセージが表示されることを確認
        $response->assertSessionHasErrors(['check_out' => '出勤時間もしくは退勤時間が不適切な値です']);

        dump('勤怠詳細画面で出勤時間を退勤時間より後にして修正ボタンを押すと、適切なエラーメッセージが表示されることを確認しました');
    }

    public function test_DisplaysErrorForBreakTimeOutsideWorkingHours()
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

        // 一般ユーザーを作成
        $user = User::create([
            'name' => 'General User',
            'email' => 'generaluser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        // 今日の勤怠レコードと休憩レコードを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
        ]);

        $attendance->breakTimes()->create([
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        // 日次勤怠一覧画面にアクセス
        $response = $this->get(route('admin.attendance.daily_list', ['date' => now()->toDateString()]));

        // 詳細リンクをクリックして勤怠詳細画面を表示
        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));

        // 勤怠詳細画面にて休憩開始時間を退勤時間より後に設定
        $response = $this->post(route('attendance.update', ['user_id' => $user->id]), [
            'date_year' => now()->format('Y'),
            'date_month_day' => now()->format('n月j日'),
            'check_in' => '09:00',
            'check_out' => '17:00',
            'breaktimes' => [
                ['start' => '18:00', 'end' => '19:00'] // 休憩開始時間を退勤時間より後に設定
            ],
            'remarks' => '休憩時間修正テスト',
        ]);

        // エラーメッセージが表示されることを確認
        $response->assertSessionHasErrors(['breaktimes.*.start' => '休憩時間が勤務時間外です']);

        dump('管理者が勤怠詳細画面で勤務時間外の休憩開始時間を設定し修正を押下した場合、適切なエラーメッセージが表示されることを確認しました');
    }

    public function testValidationErrorWhenBreakEndIsAfterCheckOut()
    {
        // 管理者ユーザーを作成してログイン
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);
        $this->actingAs($adminUser);

        // 一般ユーザーを作成し、勤怠レコードと休憩レコードを作成
        $user = User::create([
            'name' => 'General User',
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
        ]);
        $attendance->breakTimes()->create([
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        // 勤怠詳細画面にアクセス
        $response = $this->get(route('attendance.detail', $attendance->id));
        $response->assertStatus(200);

        // 修正リクエストを送信
        $response = $this->post(route('attendance.update', ['user_id' => $user->id]), [
            'date_year' => now()->format('Y'),
            'date_month_day' => now()->format('n月j日'),
            'check_in' => '09:00',
            'check_out' => '17:00',
            'breaktimes' => [
                ['start' => '12:00', 'end' => '18:00'], // 休憩終了時間を退勤時間より後に設定
            ],
            'remarks' => 'Valid remarks.',
        ]);

        // バリデーションエラーメッセージを確認
        $response->assertSessionHasErrors(['breaktimes.*.end' => '休憩時間が勤務時間外です']);

        dump('管理者が勤怠詳細画面で勤務時間外の休憩終了時間を設定し修正を押下した場合、適切なエラーメッセージが表示されることを確認しました');
    }

    public function testValidationErrorWhenRemarksIsEmpty()
    {
        // 管理者ユーザーを作成してログイン
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);
        $this->actingAs($adminUser);

        // 一般ユーザーを作成し、勤怠レコードと休憩レコードを作成
        $user = User::create([
            'name' => 'General User',
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
        ]);
        $attendance->breakTimes()->create([
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        // 勤怠詳細画面にアクセス
        $response = $this->get(route('attendance.detail', $attendance->id));
        $response->assertStatus(200);

        // 修正リクエストを送信
        $response = $this->post(route('attendance.update', ['user_id' => $user->id]), [
            'date_year' => now()->format('Y'),
            'date_month_day' => now()->format('n月j日'),
            'check_in' => '09:00',
            'check_out' => '17:00',
            'breaktimes' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'remarks' => '', // 備考欄を空欄に設定
        ]);

        // バリデーションエラーメッセージを確認
        $response->assertSessionHasErrors(['remarks' => '備考を記入してください']);

        dump('管理者が勤怠詳細画面で備考を記入せずに修正ボタンを押下した場合、適切なエラーメッセージが表示されることを確認しました');
    }

}
