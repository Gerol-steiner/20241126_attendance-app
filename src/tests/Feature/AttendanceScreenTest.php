<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\StatusChange;
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

    /** @test */
    public function it_displays_check_in_button_and_updates_status_to_working_after_check_in()
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

        // 勤怠画面にGETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ビューに「出勤」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" class="action-button">出勤</button>', false);

        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        dump('勤務外のユーザーに対し、勤怠画面に"出勤"ボタンが表示されていることを確認しました');

        // 出勤ボタンを押す
        $this->post(route('attendance.checkIn'));

        // 勤怠画面に再度GETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ステータスが「出勤中」となっていることを確認
        $response->assertSee('出勤中');

        dump('出勤処理後、勤怠画面のステータスが"出勤中"となっていることを確認しました');
    }

    /** @test */
    public function it_does_not_display_check_in_button_for_checked_out_user()
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

        // 勤怠レコードを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => now()->subHours(8)->toTimeString(),
            'check_out' => now()->toTimeString(),
        ]);

        // ステータス変更を記録（退勤済）
        StatusChange::create([
            'attendance_id' => $attendance->id,
            'status_id' => 4, // 退勤済
            'changed_at' => now(),
        ]);

        // 勤怠画面にGETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ビューに「出勤」ボタンが表示されていないことを確認
        $response->assertDontSee('<button type="submit" class="action-button">出勤</button>', false);

        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        dump('退勤済みのユーザーには"出勤"ボタンが表示されていないことを確認しました');
    }

    /** @test */
    public function it_displays_user_check_in_time_on_admin_daily_attendance_list()
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
            'name' => 'General User',
            'email' => 'generaluser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        $this->actingAs($user);

        // 出勤ボタンを押す
        $this->post(route('attendance.checkIn'));

        // ログアウト
        auth()->logout();

        // 管理者ユーザーを作成してログイン
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'adminuser@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);

        $this->actingAs($adminUser);

        // 管理者の日次勤怠一覧画面にGETリクエストを送信
        $response = $this->get(route('admin.attendance.daily_list', ['date' => now()->format('Y-m-d')]));

        // 一般ユーザーの出勤時刻が表示されていることを確認
        $response->assertSeeInOrder(['General User', now()->format('H:i')]);

        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        dump('管理者の日次勤怠一覧に一般ユーザーの出勤時刻が正確に表示されていることを確認しました');
    }

    /** @test */
    public function it_displays_break_start_button_and_changes_status_to_breaking()
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

        // 勤怠レコードを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => now()->subHours(2)->toTimeString(),
        ]);

        // ステータス変更を記録（出勤中）
        StatusChange::create([
            'attendance_id' => $attendance->id,
            'status_id' => 2, // 出勤中
            'changed_at' => now(),
        ]);

        // 勤怠画面にGETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ビューに「休憩入」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" class="action-button break-button">休憩入</button>', false);


        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        dump('勤務中のユーザーに対し、勤怠画面に"休憩入"ボタンが表示されていることを確認しました');

        // 休憩入ボタンを押す
        $this->post(route('attendance.startBreak'));

        // 勤怠画面に再度GETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ステータスが「休憩中」となっていることを確認
        $response->assertSee('休憩中');

        dump('休憩入の処理後、勤怠画面のステータスが"休憩中"となっていることを確認しました');
    }

    /** @test */
    public function it_displays_break_start_and_end_buttons_correctly()
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

        // 勤怠レコードを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => now()->subHours(2)->toTimeString(),
        ]);

        // ステータス変更を記録（出勤中）
        StatusChange::create([
            'attendance_id' => $attendance->id,
            'status_id' => 2, // 出勤中
            'changed_at' => now(),
        ]);

        // 勤怠画面にGETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ビューに「休憩入」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" class="action-button break-button">休憩入</button>', false);

        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        // 休憩入ボタンを押す
        $this->post(route('attendance.startBreak'));

        // 勤怠画面に再度GETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ビューに「休憩戻」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" class="action-button break-button">休憩戻</button>', false);

        // 休憩戻ボタンを押す
        $this->post(route('attendance.endBreak'));

        // 勤怠画面に再度GETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ビューに「休憩入」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" class="action-button break-button">休憩入</button>', false);

        dump('休憩終了後、勤怠画面に"休憩入"ボタンが再表示されていることを確認しました');
    }

    /** @test */
    public function it_displays_break_buttons_and_changes_status_correctly()
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

        // 勤怠レコードを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => now()->subHours(2)->toTimeString(),
        ]);

        // ステータス変更を記録（出勤中）
        StatusChange::create([
            'attendance_id' => $attendance->id,
            'status_id' => 2, // 出勤中
            'changed_at' => now(),
        ]);

        // 勤怠画面にGETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ビューに「休憩入」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" class="action-button break-button">休憩入</button>', false);

        // 休憩入ボタンを押す
        $this->post(route('attendance.startBreak'));

        // 勤怠画面に再度GETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ビューに「休憩戻」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" class="action-button break-button">休憩戻</button>', false);

        // 休憩戻ボタンを押す
        $this->post(route('attendance.endBreak'));

        // 勤怠画面に再度GETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ステータスが「出勤中」となっていることを確認
        $response->assertSee('出勤中');

        dump('休憩終了後、勤怠画面に"出勤中"ステータスが再表示されていることを確認しました');
    }

    /** @test */
    public function it_displays_break_buttons_correctly_in_multiple_transitions()
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

        // 勤怠レコードを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => now()->subHours(2)->toTimeString(),
        ]);

        // ステータス変更を記録（出勤中）
        StatusChange::create([
            'attendance_id' => $attendance->id,
            'status_id' => 2, // 出勤中
            'changed_at' => now(),
        ]);

        // 勤怠画面にGETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ビューに「休憩入」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" class="action-button break-button">休憩入</button>', false);

        // 休憩入ボタンを押す
        $this->post(route('attendance.startBreak'));

        // 勤怠画面に再度GETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ビューに「休憩戻」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" class="action-button break-button">休憩戻</button>', false);

        // 休憩戻ボタンを押す
        $this->post(route('attendance.endBreak'));

        // 勤怠画面に再度GETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ビューに「休憩入」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" class="action-button break-button">休憩入</button>', false);

        // 休憩入ボタンを再度押す
        $this->post(route('attendance.startBreak'));

        // 勤怠画面に再度GETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ビューに「休憩戻」ボタンが再表示されていることを確認
        $response->assertSee('<button type="submit" class="action-button break-button">休憩戻</button>', false);

        dump('休憩終了後に休憩入を再押下すると、勤怠画面に"休憩戻"ボタンが再表示されていることを確認しました');
    }

    /** @test */
    public function it_displays_user_break_times_on_admin_attendance_detail_screen()
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
            'name' => 'General User',
            'email' => 'generaluser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        $this->actingAs($user);

        // 勤怠レコードを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => now()->subHours(3)->toTimeString(),
        ]);

        // ステータス変更を記録（出勤中）
        StatusChange::create([
            'attendance_id' => $attendance->id,
            'status_id' => 2, // 出勤中
            'changed_at' => now()->subHours(2), // 修正済み
        ]);

        // 休憩入ボタンを押す
        $this->post(route('attendance.startBreak'));

        // 休憩戻ボタンを押す
        $this->post(route('attendance.endBreak'));

        // ログアウト
        auth()->logout();

        // 管理者ユーザーを作成してログイン
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'adminuser@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);

        $this->actingAs($adminUser);

        // 管理者の勤怠詳細画面にGETリクエストを送信
        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));

        // 一般ユーザーの休憩入時間と休憩戻時間が表示されていることを確認
        $breakStart = now()->format('H:i');
        $breakEnd = now()->format('H:i');

        $response->assertSeeInOrder([
            'General User', // ユーザー名
            $breakStart,    // 休憩入時間
            $breakEnd,      // 休憩戻時間
        ]);



        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        dump('管理者の勤怠詳細画面に一般ユーザーが登録した休憩入時間と休憩戻時間が正確に表示されていることを確認しました');
    }

    /** @test */
    public function it_displays_retirement_button_and_changes_status_to_finished()
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
            'name' => 'General User',
            'email' => 'generaluser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        $this->actingAs($user);

        // 勤怠レコードを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => now()->subHours(3)->toTimeString(),
        ]);

        // ステータス変更を記録（出勤中）
        StatusChange::create([
            'attendance_id' => $attendance->id,
            'status_id' => 2, // 出勤中
            'changed_at' => now()->subHours(2), // 修正済み
        ]);

        // 勤怠画面にGETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // 画面に「退勤」ボタンが表示されていることを確認
        $response->assertSee('<button type="submit" class="action-button">退勤</button>', false);

        // 退勤ボタンを押す
        $this->post(route('attendance.checkOut'));

        // 勤怠画面に再度GETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // ステータスが「退勤済」となっていることを確認
        $response->assertSee('退勤済');

        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        dump('出勤中ユーザーの勤怠画面に表示された退勤ボタンを押下すると、勤怠画面のステータスが"退勤済"となっていることを確認しました');
    }

    /** @test */
    public function it_displays_user_check_in_and_out_times_on_admin_daily_attendance_list()
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
            'name' => 'General User',
            'email' => 'generaluser@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        $this->actingAs($user);

        // 勤怠画面にGETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // 出勤ボタンが画面に表示されていることを確認
        $response->assertSee('<button type="submit" class="action-button">出勤</button>', false);

        // 出勤ボタンを押す
        $this->post(route('attendance.checkIn'));

        // 勤怠画面に再度GETリクエストを送信
        $response = $this->get(route('attendance.index'));

        // 退勤ボタンが画面に表示されていることを確認
        $response->assertSee('<button type="submit" class="action-button">退勤</button>', false);

        // 退勤ボタンを押す
        $this->post(route('attendance.checkOut'));

        // ログアウト
        auth()->logout();

        // 管理者ユーザーを作成してログイン
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'adminuser@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);

        $this->actingAs($adminUser);

        // 管理者の日次勤怠一覧画面にGETリクエストを送信
        $response = $this->get(route('admin.attendance.daily_list', ['date' => now()->format('Y-m-d')]));

        // 一般ユーザーの出勤時刻と退勤時刻が表示されていることを確認
        $response->assertSeeInOrder([
            'General User',
            now()->format('H:i'), // 出勤時刻
            now()->format('H:i'), // 退勤時刻
        ]);

        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        dump('勤務外ユーザーが行った出勤・退勤処理が、管理者ユーザーの日次勤怠一覧画面にて表示されていることを確認しました');
    }

}