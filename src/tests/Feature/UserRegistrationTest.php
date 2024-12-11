<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_validation_error_when_name_is_missing()
    {
        // 入力データを準備
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 登録フォームにPOSTリクエストを送信
        $response = $this->post('/register', $data);

        // バリデーションエラーの検証
        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);

        // ページがリダイレクトされることを確認
        $response->assertRedirect();

        // 古い入力値が保持されていることを確認
        $this->assertEquals(session('_old_input')['email'], $data['email']);

        dump('「お名前を入力してください」というバリデーションメッセージを確認しました');
    }

    /** @test */
    public function it_displays_validation_error_when_email_is_missing()
    {
        // 入力データを準備
        $data = [
            'name' => 'Test User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 登録フォームにPOSTリクエストを送信
        $response = $this->post('/register', $data);

        // バリデーションエラーの検証
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);

        // ページがリダイレクトされることを確認
        $response->assertRedirect();

        dump('「メールアドレスを入力してください」というバリデーションメッセージを確認しました');
    }

    /** @test */
    public function it_displays_validation_error_when_password_is_too_short()
    {
        // 入力データを準備
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ];

        // 登録フォームにPOSTリクエストを送信
        $response = $this->post('/register', $data);

        // バリデーションエラーの検証
        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);

        // ページがリダイレクトされることを確認
        $response->assertRedirect();

        dump('「パスワードは8文字以上で入力してください」というバリデーションメッセージを確認しました');
    }

    /** @test */
    public function it_displays_validation_error_when_passwords_do_not_match()
    {
        // 入力データを準備
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ];

        // 登録フォームにPOSTリクエストを送信
        $response = $this->post('/register', $data);

        // バリデーションエラーの検証
        $response->assertSessionHasErrors(['password_confirmation' => 'パスワードと一致しません']);

        // ページがリダイレクトされることを確認
        $response->assertRedirect();

        dump('「パスワードと一致しません」というバリデーションメッセージを確認しました');
    }

    /** @test */
    public function it_displays_validation_error_when_password_is_missing()
    {
        // 入力データを準備
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_confirmation' => 'password123', // 確認用パスワードだけ入力
        ];

        // 登録フォームにPOSTリクエストを送信
        $response = $this->post('/register', $data);

        // バリデーションエラーの検証
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);

        // ページがリダイレクトされることを確認
        $response->assertRedirect();

        dump('「パスワードを入力してください」というバリデーションメッセージを確認しました');
    }

    /** @test */
    public function it_registers_user_and_saves_to_database()
    {
        // 入力データを準備
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 登録フォームにPOSTリクエストを送信
        $response = $this->post('/register', $data);

        // データベースにデータが保存されていることを確認
        $this->assertDatabaseHas('users', [
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        // ページが正しい場所にリダイレクトされていることを確認
        $response->assertRedirect(route('registration.pending'));

        dump('登録したユーザー情報がデータベースに保存されていることを確認しました');
    }

}
