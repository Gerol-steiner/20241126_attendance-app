<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠管理アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/admin/approve_request.css') }}" />
    <!-- Flatpickrのスタイルシートの読み込み -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

</head>

<body>
    <header class="header">
        <div class="header_inner">
            @if (Auth::user() && Auth::user()->is_admin)
                <!-- 管理者用ヘッダー -->
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
            @else
                <!-- 一般ユーザー用ヘッダー -->
                <a class="header__logo" href="/attendance">
                    <img src="{{ asset('images/logo.svg') }}" alt="COACHTECH ロゴ" class="logo-image">
                </a>
                <nav class="header__nav">
                    <a class="header__link" href="/attendance">勤怠</a>
                    <a class="header__link" href="/attendance/list">勤怠一覧</a>
                    <a class="header__link" href="/stamp_correction_request/list" role="button">申請一覧</a>
                    <form action="{{ route('logout') }}" method="POST" class="header__logout-form">
                        @csrf
                        <button type="submit" class="header__logout-button">ログアウト</button>
                    </form>
                </nav>
            @endif
        </div>
    </header>


    <main>
<!--開発用-->
<p style="margin: 0;">ユーザーID: {{ Auth::user()->id }}</p>
<p style="margin: 0;">修正申請ID: {{ $modificationRequest->id }}</p>
<p style="margin: 0;">承認者ID: {{ $modificationRequest->approved_by ?? 'null' }}</p>

        <div class="attendance-wrapper">
            <h1>勤怠詳細</h1>

            <form action="{{ route('attendance_modification.approve', ['attendance_correct_request' => $modificationRequest->id]) }}" method="POST" class="attendance-form">
                @csrf
                <table class="attendance-detail-table">
                    <!-- 1行目 -->
                    <tr>
                        <td class="col-1">名前</td>
                        <td class="col-2" colspan="4"><span id="name">{{ $modificationRequest->staff->name }}</span></td>
                    </tr>
                    <!-- 2行目 -->
                    <tr>
                        <td class="col-1">日付</td>
                        <td class="col-2">
                            <div class="year-input-container">
                                <span id="date_year">{{ Carbon\Carbon::parse($modificationRequest->date)->format('Y年') }}</span>
                                <span class="year-suffix"></span>
                            </div>
                        </td>
                        <td class="col-3"></td> <!-- 空欄 -->
                        <td class="col-4">
                            <span id="date_month_day">{{ Carbon\Carbon::parse($modificationRequest->date)->format('n月j日') }}</span>
                        </td>
                        <td class="col-5"></td> <!-- 空白の列 -->
                    </tr>
                    <!-- 3行目 -->
                    <tr>
                        <td class="col-1">出勤・退勤</td>
                        <td class="col-2">
                            <span>{{ $modificationRequest->check_in ? Carbon\Carbon::parse($modificationRequest->check_in)->format('H:i') : '---' }}</span>
                        </td>
                        <td class="col-3">
                            <span class="separator">～</span>
                        </td>
                        <td class="col-4">
                            <span>{{ $modificationRequest->check_out ? Carbon\Carbon::parse($modificationRequest->check_out)->format('H:i') : '---' }}</span>
                        </td>
                        <td class="col-5"></td> <!-- 空白の列 -->
                    </tr>
                    <!-- 4行目 -->
                    @forelse ($modificationRequest->breakTimeModifications as $index => $breaktime)
                        <tr>
                            <td class="col-1">休憩{{ $index + 1 }}</td>
                            <td class="col-2">
                                <span>{{ $breaktime->break_start ? Carbon\Carbon::parse($breaktime->break_start)->format('H:i') : '---' }}</span>
                            </td>
                            <td class="col-3">
                                <span class="separator">～</span>
                            </td>
                            <td class="col-4">
                                <span>{{ $breaktime->break_end ? Carbon\Carbon::parse($breaktime->break_end)->format('H:i') : '---' }}</span>
                            </td>
                            <td class="col-5"></td> <!-- 空白の列 -->
                        </tr>
                    @empty
                        <tr>
                            <td class="col-1">休憩</td>
                            <td class="col-2" colspan="2">
                                <span style="font-weight: 400;">（休憩時間は登録されていません）</span>
                            </td>
                            <td class="col-4"></td>
                            <td class="col-5"></td> <!-- 空白の列 -->
                        </tr>
                    @endforelse
                    <!-- 5行目 -->
                    <tr>
                        <td class="col-1">備考</td>
                        <td colspan="3" class="col-2">
                            <span>{{ $modificationRequest->remark }}</span>
                        </td>
                        <td class="col-5"></td> <!-- 空白の列 -->
                    </tr>
                </table>

                <div class="button-container">
                    @if ($modificationRequest->approved_by)
                        <!-- 承認済みの場合 -->
                        <span class="btn-approved">承認済み</span>
                    @else
                        @if (Auth::user()->is_admin)
                            <!-- 管理者の場合 -->
                            <form action="{{ route('attendance_modification.approve_request', ['attendance_correct_request' => $modificationRequest->id]) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-approve">承認</button>
                            </form>
                        @else
                            <!-- 一般ユーザーの場合 -->
                            <p class="pending-message">※ 承認待ちのため修正はできません。</p>
                        @endif
                    @endif
                </div>
            </form>
        </div>


    </main>
</body>
</html>
