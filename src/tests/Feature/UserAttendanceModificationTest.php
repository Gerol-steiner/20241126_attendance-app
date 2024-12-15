<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceModification;
use App\Models\BreakTimeModification;

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

    /** @test */
    public function it_displays_pending_request_and_details_correctly()
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

        // 勤怠修正を POST リクエストで送信
        $this->post(route('attendance.update', ['user_id' => $user->id]), [
            'date_year' => now()->format('Y'),
            'date_month_day' => now()->format('n月j日'),
            'check_in' => '07:00',
            'check_out' => '20:00',
            'breaktimes' => [
                ['start' => '15:00', 'end' => '16:00']
            ],
            'remarks' => '業務の開始が早まり、終了が遅くなったため修正を申請します。',
        ]);

        // ログアウト
        auth()->logout();

        // 管理者ユーザーを作成しログイン
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'adminuser@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);

        $this->actingAs($adminUser);

        // 申請一覧画面にGETリクエストを送信
        $response = $this->get(route('admin.requests', ['tab' => 'pending']));

        // 承認待ちの申請が表示されていることを確認
        $response->assertSee('承認待ち');
        $response->assertSee($user->name);
        $response->assertSee(now()->format('Y/m/d'));
        $response->assertSee('業務の開始が早まり、終了が遅くなったため修正を申請します。');
        $response->assertSee('<a href="' . route('attendance_modification.approve', ['attendance_correct_request' => AttendanceModification::first()->id]) . '">詳細</a>', false);

        // 詳細リンクをクリックして承認画面に遷移
        $response = $this->get(route('attendance_modification.approve', ['attendance_correct_request' => AttendanceModification::first()->id]));

        // 承認画面が正しく表示されていることを確認
        $response->assertSee($user->name);
        $response->assertSee('<span id="date_year">' . now()->format('Y年') . '</span>', false); // 年の部分
        $response->assertSee('<span id="date_month_day">' . now()->format('n月j日') . '</span>', false); // 月日部分
        $response->assertSee('07:00'); // 修正後の出勤時間
        $response->assertSee('20:00'); // 修正後の退勤時間
        $response->assertSee('15:00'); // 修正後の休憩開始時間
        $response->assertSee('16:00'); // 修正後の休憩終了時間
        $response->assertSee('業務の開始が早まり、終了が遅くなったため修正を申請します。');

        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        dump('一般ユーザーが行った修正申請が、管理者の申請一覧に「承認待ち」として表示され、「詳細」のクリックで承認画面に遷移することを確認しました');
    }

    /** @test */
    public function it_displays_multiple_pending_requests_in_user_view()
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

        // 昨日と本日の勤怠レコードを作成
        $yesterdayAttendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->subDay()->toDateString(),
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
        ]);

        $todayAttendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
        ]);

        // 昨日の勤怠修正を POST リクエストで送信
        $this->post(route('attendance.update', ['user_id' => $user->id]), [
            'date_year' => now()->subDay()->format('Y'),
            'date_month_day' => now()->subDay()->format('n月j日'),
            'check_in' => '08:30',
            'check_out' => '18:30',
            'breaktimes' => [
                ['start' => '12:30', 'end' => '13:30']
            ],
            'remarks' => '勤務開始が30分早まり、終了が30分遅くなりました。',
        ]);

        // 本日の勤怠修正を POST リクエストで送信
        $this->post(route('attendance.update', ['user_id' => $user->id]), [
            'date_year' => now()->format('Y'),
            'date_month_day' => now()->format('n月j日'),
            'check_in' => '07:00',
            'check_out' => '20:00',
            'breaktimes' => [
                ['start' => '15:00', 'end' => '16:00']
            ],
            'remarks' => '業務の開始が早まり、終了が遅くなったため修正を申請します。',
        ]);

        // 一般ユーザーとして申請一覧画面にGETリクエストを送信
        $response = $this->get(route('admin.requests', ['tab' => 'pending']));

        // 承認待ちの申請が2件表示されていることを確認
        $response->assertSee('承認待ち');
        $response->assertSee($user->name);
        $response->assertSee(now()->subDay()->format('Y/m/d')); // 昨日の申請
        $response->assertSee(now()->format('Y/m/d')); // 本日の申請
        $response->assertSee('勤務開始が30分早まり、終了が30分遅くなりました。');
        $response->assertSee('業務の開始が早まり、終了が遅くなったため修正を申請します。');

        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        dump('一般ユーザーが行った修正申請が、自身の申請一覧に「承認待ち」として全件表示されることを確認しました');
    }

    /** @test */
    public function it_approves_multiple_pending_requests()
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

        // 昨日と本日の勤怠レコードを作成
        $yesterdayAttendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->subDay()->toDateString(),
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
        ]);

        $todayAttendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
        ]);

        // 勤怠修正を POST リクエストで送信
        $this->post(route('attendance.update', ['user_id' => $user->id]), [
            'date_year' => now()->subDay()->format('Y'),
            'date_month_day' => now()->subDay()->format('n月j日'),
            'check_in' => '08:30',
            'check_out' => '18:30',
            'breaktimes' => [
                ['start' => '12:30', 'end' => '13:30']
            ],
            'remarks' => '昨日の勤務修正',
        ]);

        $this->post(route('attendance.update', ['user_id' => $user->id]), [
            'date_year' => now()->format('Y'),
            'date_month_day' => now()->format('n月j日'),
            'check_in' => '07:00',
            'check_out' => '20:00',
            'breaktimes' => [
                ['start' => '15:00', 'end' => '16:00']
            ],
            'remarks' => '本日の勤務修正',
        ]);

        // ログアウト
        auth()->logout();

        // 管理者ユーザーを作成しログイン
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'adminuser@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);

        $this->actingAs($adminUser);

        // 申請一覧画面にGETリクエストを送信 ['tab' => 'pending']
        $response = $this->get(route('admin.requests', ['tab' => 'pending']));

        // 本日の修正申請について詳細リンクをクリックして承認画面に遷移
        $response = $this->get(route('attendance_modification.approve', ['attendance_correct_request' => AttendanceModification::where('remark', '本日の勤務修正')->first()->id]));

        // 承認を行う
        $this->post(route('attendance_modification.approve_request', ['attendance_correct_request' => AttendanceModification::where('remark', '本日の勤務修正')->first()->id]));

        // 申請一覧画面にGETリクエストを送信 ['tab' => 'pending']
        $response = $this->get(route('admin.requests', ['tab' => 'pending']));

        // 昨日の修正申請について詳細リンクをクリックして承認画面に遷移
        $response = $this->get(route('attendance_modification.approve', ['attendance_correct_request' => AttendanceModification::where('remark', '昨日の勤務修正')->first()->id]));

        // 承認を行う
        $this->post(route('attendance_modification.approve_request', ['attendance_correct_request' => AttendanceModification::where('remark', '昨日の勤務修正')->first()->id]));

        // 申請一覧画面にGETリクエストを送信 ['tab' => 'approved']
        $response = $this->get(route('admin.requests', ['tab' => 'approved']));

        // 承認済みの申請が2件表示されていることを確認
        $response->assertSee('承認済み');
        $response->assertSee('昨日の勤務修正');
        $response->assertSee('本日の勤務修正');

        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        dump('管理者により承認された一般ユーザーの修正申請が、管理者の承認一覧の「承認済み」に全て表示されていることを確認しました');
    }

    /** @test */
    public function it_displays_user_pending_request_and_details_correctly()
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

        // 勤怠修正を POST リクエストで送信
        $this->post(route('attendance.update', ['user_id' => $user->id]), [
            'date_year' => now()->format('Y'),
            'date_month_day' => now()->format('n月j日'),
            'check_in' => '07:00',
            'check_out' => '20:00',
            'breaktimes' => [
                ['start' => '15:00', 'end' => '16:00']
            ],
            'remarks' => '業務の開始が早まり、終了が遅くなったため修正を申請します。',
        ]);

        // 申請一覧画面にGETリクエストを送信
        $response = $this->get(route('admin.requests', ['tab' => 'pending']));

        // 承認待ちの申請が表示されていることを確認
        $response->assertSee('承認待ち');
        $response->assertSee($user->name);
        $response->assertSee(now()->format('Y/m/d'));
        $response->assertSee('業務の開始が早まり、終了が遅くなったため修正を申請します。');
        $response->assertSee('<a href="' . route('attendance_modification.approve', ['attendance_correct_request' => AttendanceModification::first()->id]) . '">詳細</a>', false);

        // 詳細リンクをクリックして承認画面に遷移
        $response = $this->get(route('attendance_modification.approve', ['attendance_correct_request' => AttendanceModification::first()->id]));

        // 承認画面が正しく表示されていることを確認
        $response->assertSee($user->name);
        $response->assertSee('<span id="date_year">' . now()->format('Y年') . '</span>', false); // 年の部分
        $response->assertSee('<span id="date_month_day">' . now()->format('n月j日') . '</span>', false); // 月日部分
        $response->assertSee('07:00'); // 修正後の出勤時間
        $response->assertSee('20:00'); // 修正後の退勤時間
        $response->assertSee('15:00'); // 修正後の休憩開始時間
        $response->assertSee('16:00'); // 修正後の休憩終了時間
        $response->assertSee('業務の開始が早まり、終了が遅くなったため修正を申請します。');

        // ページが正常に読み込まれたことを確認
        $response->assertStatus(200);

        dump('一般ユーザーが行った修正申請が申請一覧に表示され、「詳細」の押下により申請詳細画面に遷移することを確認しました');
    }

}
