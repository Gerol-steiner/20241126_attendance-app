<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StaffController extends Controller
{
    public function index()
    {
        // ログインしていなければログインページにリダイレクト
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }

        // 一般ユーザー（is_admin = 0）の全データを取得
        $users = User::where('is_admin', 0)->get();

        // ビューにデータを渡す
        return view('admin.staff_list', [
            'users' => $users,
        ]);
    }
}
