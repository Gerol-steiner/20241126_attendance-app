<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠管理アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/auth/registration_pending.css') }}" />
</head>

<body>
    <header class="header">
        <a class="header__logo" href="/">
            <img src="{{ asset('images/logo.svg') }}" alt="COACHTECH ロゴ" class="logo-image">
        </a>
    </header>

    <main>

    @if (Auth::check())
        <p>ユーザーID: {{ Auth::user()->id }}</p>
    @else
        <p>ログインしていません。</p>
    @endif

        <div class="container">
            <h1>「/」へのリダイレクト</h1>

        </div>

    {{-- 成功メッセージ --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- 情報メッセージ --}}
    @if (session('info'))
        <div class="alert alert-info">
            {{ session('info') }}
        </div>
    @endif

    {{-- エラーメッセージ --}}
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    

        <form action="{{ route('logout') }}" method="POST" style="display: inline;">
    @csrf
    <button type="submit">ログアウト（開発用）</button>
</form>

    </main>

</html>

<form action="{{ route('logout') }}" method="POST" style="display: inline;">
    @csrf
    <button type="submit">ログアウト</button>
</form>