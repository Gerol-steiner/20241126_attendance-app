<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠管理アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/admin/staff_list.css') }}" />
</head>

<body>
    <header class="header">
        <div class="header_inner">
            <a class="header__logo" href="/admin/attendance/list">
                <img src="{{ asset('images/logo.svg') }}" alt="COACHTECH ロゴ" class="logo-image">
            </a>
            <nav class="header__nav">
                <a class="header__link" href="/admin/attendance/list">勤怠一覧</a>
                <a class="header__link" href="/admin/staff/list">スタッフ一覧</a>
                <a class="header__link" href="/stamp_correction_request/list" role="button">申請一覧</a>
                <form action="{{ route('admin.logout') }}" method="POST" class="header__logout-form">
                    @csrf
                    <button type="submit" class="header__logout-button">ログアウト</button>
                </form>
            </nav>
        </div>
    </header>

    <main>


    <!--開発用-->
    @if (Auth::check())
        <p style="margin: 0;">ユーザーID: {{ Auth::user()->id }}</p>
    @else
        <p style="margin: 0;">ログインしていません。</p>
    @endif


        <div class="staff-wrapper">
            <h1>スタッフ一覧</h1>
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>メールアドレス</th>
                        <th>月次勤怠</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td><a href="/admin/attendance/staff/{{ $user->id }}">詳細</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>
