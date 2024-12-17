<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceModification;
use App\Models\BreakTimeModification;
use Carbon\Carbon; 

class AdminAttendanceModificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_DisplaysPendingRequestsCorrectlyForAdmin()
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

        // 勤怠レコードと修正申請レコードの作成
        $dates = [now()->subDay()->toDateString(), now()->toDateString()]; // 昨日と本日

        $modifications = [];
        foreach ([$user1, $user2] as $userIndex => $user) {
            foreach ($dates as $index => $date) {
                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'date' => $date,
                    'check_in' => sprintf('08:%02d:00', $userIndex * 10 + $index),
                    'check_out' => sprintf('17:%02d:00', $userIndex * 10 + $index),
                ]);

                $remark = "修正申請 {$user->id}"; // 固定形式のremark
                $modification = AttendanceModification::create([
                    'attendance_id' => $attendance->id,
                    'date' => $date,
                    'check_in' => sprintf('08:%02d', $userIndex * 10 + $index),
                    'check_out' => sprintf('17:%02d', $userIndex * 10 + $index),
                    'remark' => $remark,
                    'staff_id' => $user->id,
                    'requested_by' => $user->id,
                    'approved_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                BreaktimeModification::create([
                    'attendance_modification_id' => $modification->id,
                    'break_start' => '12:00:00',
                    'break_end' => '13:00:00',
                ]);

                $modifications[] = $modification;
            }
        }

        // 管理者ユーザーを作成してログイン
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);

        $this->actingAs($adminUser);

        // 申請一覧画面を表示
        $response = $this->get(route('admin.requests', ['tab' => 'pending']));

        // 申請一覧画面に4件の申請が表示されていることを確認
        foreach ($modifications as $modification) {
            $response->assertSee('承認待ち');
            $response->assertSee($modification->attendance->user->name);
            $response->assertSee(Carbon::parse($modification->date)->format('Y/m/d'));
            $response->assertSee($modification->remark);
            $response->assertSee(now()->format('Y/m/d'));
            $response->assertSee('<a href="' . route('attendance_modification.approve', ['attendance_correct_request' => $modification->id]) . '">詳細</a>', false);
        }

        dump('管理者用申請一覧画面にて、未承認の修正申請がすべて正しく表示されていることを確認しました');
    }

    public function test_DisplaysApprovedRequestsCorrectlyForAdmin()
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

        // 管理者ユーザーを作成
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);

        // 勤怠レコードと修正申請レコードの作成
        $dates = [now()->subDay()->toDateString(), now()->toDateString()]; // 昨日と本日

        $modifications = [];
        foreach ([$user1, $user2] as $userIndex => $user) {
            foreach ($dates as $index => $date) {
                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'date' => $date,
                    'check_in' => sprintf('08:%02d:00', $userIndex * 10 + $index),
                    'check_out' => sprintf('17:%02d:00', $userIndex * 10 + $index),
                ]);
                $remark = "修正申請 {$user->id}"; // 固定形式のremark
                $modification = AttendanceModification::create([
                    'attendance_id' => $attendance->id,
                    'date' => $date,
                    'check_in' => sprintf('08:%02d', $userIndex * 10 + $index),
                    'check_out' => sprintf('17:%02d', $userIndex * 10 + $index),
                    'remark' => $remark,
                    'staff_id' => $user->id,
                    'requested_by' => $user->id,
                    'approved_by' => $adminUser->id, // 承認済みにする
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                BreaktimeModification::create([
                    'attendance_modification_id' => $modification->id,
                    'break_start' => '12:00:00',
                    'break_end' => '13:00:00',
                ]);

                $modifications[] = $modification;
            }
        }

        $this->actingAs($adminUser);

        // 申請一覧画面を承認済みタブで表示
        $response = $this->get(route('admin.requests', ['tab' => 'approved']));

        // 申請一覧画面に4件の承認済み申請が表示されていることを確認
        foreach ($modifications as $modification) {
            $response->assertSee('承認待ち');
            $response->assertSee($modification->attendance->user->name);
            $response->assertSee(Carbon::parse($modification->date)->format('Y/m/d'));
            $response->assertSee($modification->remark);
            $response->assertSee(now()->format('Y/m/d'));
            $response->assertSee('<a href="' . route('attendance_modification.approve', ['attendance_correct_request' => $modification->id]) . '">詳細</a>', false);
        }

        dump('管理者用申請一覧画面にて、承認済みの修正申請がすべて正しく表示されていることを確認しました');
    }

    public function test_DisplayApprovalRequestDetailsCorrectly()
    {
        // 一般ユーザーを作成
        $user = User::create([
            'name' => 'User One',
            'email' => 'userone@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        // 管理者ユーザーを作成
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);

        // 勤怠レコード作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
        ]);

        // 修正申請レコード作成
        $modification = AttendanceModification::create([
            'attendance_id' => $attendance->id,
            'date' => now()->toDateString(),
            'check_in' => '09:10',
            'check_out' => '17:50',
            'remark' => '出勤・退勤時刻の修正',
            'staff_id' => $user->id,
            'requested_by' => $user->id,
            'approved_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 休憩修正レコード作成
        $breaktime = BreaktimeModification::create([
            'attendance_modification_id' => $modification->id,
            'break_start' => '12:10:00',
            'break_end' => '12:50:00',
        ]);

        $this->actingAs($adminUser);

        // 申請一覧の詳細画面にアクセス
        $response = $this->get(route('attendance_modification.approve', ['attendance_correct_request' => $modification->id]));

        // 修正申請詳細画面に情報が正しく表示されていることを確認
        $response->assertSee($user->name);
        $response->assertSee(now()->format('Y年'));
        $response->assertSee(now()->format('n月j日'));
        $response->assertSee('09:10');
        $response->assertSee('17:50');
        $response->assertSee('12:10');
        $response->assertSee('12:50');
        $response->assertSee('出勤・退勤時刻の修正');

        dump('管理者用の修正申請承認画面にて、修正申請の詳細情報が正しく表示されていることを確認しました');
    }

    public function test_ApproveRequestAndReflectInMonthlyAttendance()
    {
        // 一般ユーザーを作成
        $user = User::create([
            'name' => 'User One',
            'email' => 'userone@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0,
        ]);

        // 管理者ユーザーを作成
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('adminpassword123'),
            'email_verified_at' => now(),
            'is_admin' => 1,
        ]);

        // 勤怠レコード作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
        ]);

        // 修正申請レコード作成
        $modification = AttendanceModification::create([
            'attendance_id' => $attendance->id,
            'date' => now()->toDateString(),
            'check_in' => '09:10',
            'check_out' => '17:50',
            'remark' => '出勤・退勤時刻の修正',
            'staff_id' => $user->id,
            'requested_by' => $user->id,
            'approved_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 休憩修正レコード作成
        $breaktime = BreaktimeModification::create([
            'attendance_modification_id' => $modification->id,
            'break_start' => '12:10:00',
            'break_end' => '12:50:00',
        ]);

        $this->actingAs($adminUser);

        // 申請一覧から詳細画面に遷移
        $response = $this->get(route('attendance_modification.approve', ['attendance_correct_request' => $modification->id]));
        $response->assertStatus(200);

        // 承認ボタンを押下して承認処理を実行
        $response = $this->post(route('attendance_modification.approve_request', ['attendance_correct_request' => $modification->id]));

        // 月次勤怠一覧画面に遷移し承認内容を確認
        $response = $this->get(route('admin.attendance.staff.monthly_list', ['id' => $user->id]));
        $response->assertStatus(200);

        // 承認後の勤怠情報が反映されていることを確認
        $response->assertSee(now()->format('m/d'));
        $response->assertSee('09:10'); // 修正後の出勤時間
        $response->assertSee('17:50'); // 修正後の退勤時間
        $response->assertSee('0:40'); // 休憩時間合計 (12:10〜12:50)
        $response->assertSee('8:00'); // 合計勤務時間 (8:40 - 0:40)

        dump('修正申請が承認され、月次勤怠一覧画面に承認内容が正しく反映されていることを確認しました。');
    }

}
