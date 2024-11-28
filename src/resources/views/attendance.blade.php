<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠管理アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}" />
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 現在時刻の更新
            function updateClock() {
                const now = new Date();
                const options = { year: 'numeric', month: 'long', day: 'numeric', weekday: 'short' };
                const formattedDate = now.toLocaleDateString('ja-JP', options).replace(/日/g, '');
                const formattedTime = now.toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' });
                document.getElementById('current-time').innerHTML = `${formattedDate}<br>${formattedTime}`;
            }

            setInterval(updateClock, 1000); // 1秒ごとに更新
            updateClock();

            // 出勤ボタンを押したときの処理
            document.getElementById('check-in-button').addEventListener('click', function () {
                axios.post('/attendance/check-in')
                    .then(response => {
                        const flashMessage = document.getElementById('flash-message');
                        // AttendanceControllerのcheckInメソッドのjson形式のレスポンスをmessageをキーとして受け取る
                        flashMessage.textContent = response.data.message;
                        flashMessage.style.display = 'block';
                        // フラッシュメッセージ表示時間
                        setTimeout(() => {
                            flashMessage.style.display = 'none';
                        }, 10000);

                        document.getElementById('buttons').innerHTML = `
                            <button id="check-out-button" class="action-button">退勤</button>
                            <button id="break-start-button" class="action-button">休憩入</button>
                        `;
                    })
                    .catch(error => {
                        console.error(error);
                        alert('エラーが発生しました。もう一度お試しください。');
                    });
            });
        });
    </script>
</head>

<body>
    <header class="header">
        <div class="header_inner">
            <a class="header__logo" href="/">
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

    <!--フラッシュメッセージ-->
    <div id="flash-message" style="display: none; background-color: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 20px;"></div>


    <div style="text-align: center; margin-top: 20px;">
        <!-- 現在時刻 -->
        <div id="current-time" style="font-size: 24px; margin-bottom: 20px;"></div>
        
        <!-- ボタン -->
        <div id="buttons">
            <button id="check-in-button" class="action-button">出勤</button>
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
        <p>出勤時刻: {{ $attendance->check_in }}</p>
        <p>退勤時刻: {{ $attendance->check_out }}</p>
        <p>休憩開始: {{ $attendance->break_start }}</p>
        <p>休憩終了: {{ $attendance->break_end }}</p>
    @else
        <p>本日のattendanceレコードはまだありません</p>
    @endif

    </main>

</html>
