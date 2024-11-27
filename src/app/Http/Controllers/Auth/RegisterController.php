<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;  // フォームリクエスト読込み
use App\Actions\Fortify\CreateNewUser; // ユーザー作成アクションをインポート
use Illuminate\Support\Facades\Auth; // ログイン処理用に追加

class RegisterController extends Controller
{
    public function register(RegisterRequest $request)
    {
        // バリデーションが通った後、CreateNewUserを呼び出す(-> CreateNewUser.php)
        $user = (new CreateNewUser())->create($request->validated());

        // メール認証用の通知を送信
        $user->sendEmailVerificationNotification();

        // ユーザー登録後の処理（確認メッセージを表示するビューへリダイレクト）
        return redirect()->route('registration.pending')->with('success', 'ユーザー仮登録が完了しました。メールをご確認ください。');
    }
}
