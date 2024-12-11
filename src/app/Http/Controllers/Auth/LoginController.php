<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        // ユーザーを取得（認証前）
        $user = User::where('email', $request->email)->first();

        // ユーザーが存在しない場合、または認証前にバリデーション
        if (!$user) {
            return back()->withErrors([
                'email' => 'ログイン情報が登録されていません。',
            ])->withInput();
        }

        // 管理者が一般用のログイン画面にアクセスした場合
        if ($user->is_admin) {
            return back()->withErrors([
                'email' => 'ログイン情報が登録されていません。',
            ])->withInput();
        }

        // ユーザーが存在し、かつメール認証が完了していない場合
        if ($user && !$user->hasVerifiedEmail()) {
            return back()->withErrors([
                'email' => 'メールアドレスの認証が完了していません。送付した認証メールをご確認ください。',
            ])->withInput();
        }

        if (Auth::attempt($credentials)) {
            // 認証に成功し、かつメール認証が完了している場合の処理
            $request->session()->regenerate();
            return redirect('/attendance');
        }

        // 認証に失敗した場合の処理
        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません。',
        ])->withInput();
    }
}
