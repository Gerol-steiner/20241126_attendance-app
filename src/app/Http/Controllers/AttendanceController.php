<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance; // 追加

class AttendanceController extends Controller
{
    public function checkIn(Request $request)
    {
        // ログインユーザーのIDを取得
        $userId = Auth::id();

        // 出勤時刻を現在時刻で設定
        $checkInTime = now();

        // 新しい出勤記録を作成
        Attendance::create([
            'user_id' => $userId, // 例えば、ユーザーIDを保存
            'check_in' => $checkInTime,
        ]);

        // 出勤後のリダイレクト
        return redirect()->back()->with('success', '出勤処理が完了しました');
    }
}
