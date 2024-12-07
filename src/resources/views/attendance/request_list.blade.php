<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠管理アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance/request_list.css') }}" />
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


        <div class="request-wrapper">
            <h1>申請一覧</h1>

            <!-- ページタブ -->
            <nav class="request-filter-nav">
                <ul class="filter-list">
                    <li class="filter-option">
                        <a href="{{ route('admin.requests', ['tab' => 'pending']) }}"
                            class="filter-link {{ $currentTab === 'pending' ? 'active' : '' }}">承認待ち</a>
                    </li>
                    <li class="filter-option">
                        <a href="{{ route('admin.requests', ['tab' => 'approved']) }}"
                            class="filter-link {{ $currentTab === 'approved' ? 'active' : '' }}">承認済み</a>
                    </li>
                </ul>
            </nav>

            <!-- テーブル -->
            <table class="request-table">
                <thead>
                    <tr>
                        <th>状態</th>
                        <th>名前</th>
                        <th>対象日時</th>
                        <th>申請理由</th>
                        <th>申請日時</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $request)
                        <tr>
                            <!-- 状態 -->
                            <td>{{ $request->approved_by ? '承認済み' : '承認待ち' }}</td>
                            <!-- 名前 -->
                            <td>{{ $request->staff ? $request->staff->name : 'ユーザー情報なし' }}</td>
                            <!-- 対象日時 -->
                            <td>{{ Carbon\Carbon::parse($request->date)->format('Y/m/d') }}</td>
                            <!-- 申請理由 -->
                            <td>{{ $request->remark }}</td>
                            <!-- 申請日時 -->
                            <td>{{ $request->created_at->format('Y/m/d') }}</td>
                            <!-- 詳細 -->
                            <td>
                                <a href="{{ route('attendance_modification.approve', ['attendance_correct_request' => $request->id]) }}">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>
