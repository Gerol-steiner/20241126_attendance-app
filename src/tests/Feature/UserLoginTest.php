<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_validation_error_when_email_is_missing()
    {
        // 一般ユーザーをデータベースに登録
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0, // 一般ユーザーフラグ
        ]);

        // 入力データを準備（emailを入力しない）
        $data = [
            'name' => 'Test User',
            'password' => 'password123',
        ];

        // ログインフォームにPOSTリクエストを送信
        $response = $this->post('/login', $data);

        // バリデーションエラーの検証
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);

        // ページがリダイレクトされることを確認
        $response->assertRedirect();

        dump('"メールアドレスを入力してください" というバリデーションメッセージを確認しました');
    }

    /** @test */
    public function it_displays_validation_error_when_password_is_missing()
    {
        // 一般ユーザーをデータベースに登録
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0, // 一般ユーザーフラグ
        ]);

        // 入力データを準備（passwordを入力しない）
        $data = [
            'email' => 'user@example.com',
        ];

        // ログインフォームにPOSTリクエストを送信
        $response = $this->post('/login', $data);

        // バリデーションエラーの検証
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);

        // ページがリダイレクトされることを確認
        $response->assertRedirect();

        dump('"パスワードを入力してください" というバリデーションメッセージを確認しました');
    }

    /** @test */
    public function it_displays_error_when_invalid_email_is_used()
    {
        // 一般ユーザーをデータベースに登録
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_admin' => 0, // 一般ユーザーフラグ
        ]);

        // 入力データを準備（間違ったemailを入力）
        $data = [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ];

        // ログインフォームにPOSTリクエストを送信
        $response = $this->post('/login', $data);

        // バリデーションエラーの検証
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません。']);

        // ページがリダイレクトされることを確認
        $response->assertRedirect();

        dump('"ログイン情報が登録されていません。" というバリデーションメッセージを確認しました');
    }
}
