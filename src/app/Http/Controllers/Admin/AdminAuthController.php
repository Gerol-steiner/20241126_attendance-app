<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AdminAuthController extends Controller
{
    // 管理者ログイン画面の表示
    public function showLoginForm()
    {
        return view('admin.login');
    }

    // ログイン処理
    public function login(Request $request)
    {
        // ユーザーを検索
        $user = User::where('email', $request->email)->first();

        // ユーザーが存在しない、または認証条件を満たさない場合
        if (!$user || !$user->email_verified_at || $user->is_admin !== 1 || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'ログイン情報が正しくありません。'])->withInput();
        }

        // ログイン処理
        Auth::login($user);

        // 管理者専用の勤怠一覧画面にリダイレクト
        return redirect()->route('attendance.list');
    }
}