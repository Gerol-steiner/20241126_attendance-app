<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠管理アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance/index.css') }}" />
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // １．【現在時刻の更新】
            function updateClock() {
                const now = new Date(); // 現在の日時を取得
                const options = { year: 'numeric', month: 'long', day: 'numeric', weekday: 'short' }; // 日付フォーマットの設定
                const formattedDate = now.toLocaleDateString('ja-JP', options).replace(/日/g, ''); // 日本語形式の日付
                const formattedTime = now.toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' }); // 時刻のフォーマット（時:分）

                // 日付を更新（HTML要素#current-dateに代入）
                document.getElementById('current-date').textContent = formattedDate;

                // 時刻を更新（HTML要素#current-time-clockに代入）
                document.getElementById('current-time-clock').textContent = formattedTime;
            }

            setInterval(updateClock, 1000); // 1秒ごとに時刻を更新
            updateClock(); // ページ読み込み時に即時更新
    </script>

</head>

<body>
    <header class="header">
        <div class="header_inner">
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
        </div>
    </header>

    <main>


    <!--フラッシュメッセージ-->
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('info'))
        <div class="alert alert-info">
            {{ session('info') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div>
        <!-- 勤務状態 -->
        <p id="current-status">
            {{ $currentStatus }}
        </p>

        <!-- 現在時刻 -->
        <div id="current-time">
            <div id="current-date"></div>
            <div id="current-time-clock"></div>
        </div>

        <!-- ボタン -->
        <div>
            @if ($currentStatus === '勤務外')
                <form method="POST" action="{{ route('attendance.checkIn') }}">
                    @csrf
                    <button type="submit" class="action-button">出勤</button>
                </form>
            @elseif ($currentStatus === '出勤中')
                <form method="POST" action="{{ route('attendance.checkOut') }}">
                    @csrf
                    <button type="submit" class="action-button">退勤</button>
                </form>
                <form method="POST" action="{{ route('attendance.startBreak') }}">
                    @csrf
                    <button type="submit" class="action-button">休憩入</button>
                </form>
            @elseif ($currentStatus === '休憩中')
                <form method="POST" action="{{ route('attendance.endBreak') }}">
                    @csrf
                    <button type="submit" class="action-button">休憩戻</button>
                </form>
            @elseif ($currentStatus === '退勤済')
                <p>お疲れさまでした。</p>
            @endif
        </div>
    </div>

    <!--開発用-->
    @if (Auth::check())
        <p>ユーザーID: {{ Auth::user()->id }}</p>
    @else
        <p>ログインしていません。</p>
    @endif

    @if ($attendance)
        <p>attendance_id: {{ $attendance->id }}</p>
        <p>$currentStatus: {{ $currentStatus }}</p>
        <p>出勤時刻: {{ $attendance->check_in }}</p>
        <p>退勤時刻: {{ $attendance->check_out }}</p>
        <p>休憩開始: {{ $attendance->break_start }}</p>
        <p>休憩終了: {{ $attendance->break_end }}</p>
    @else
        <p>本日のattendanceレコードはまだありません</p>
    @endif

    </main>

</html>
