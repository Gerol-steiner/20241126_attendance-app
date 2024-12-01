<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠管理アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance/detail.css') }}" />
    <!-- Flatpickrのスタイルシートの読み込み -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- FlatpickrのJavaScriptライブラリの読み込み -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

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
    <!--開発用-->
    @if (Auth::check())
        <p style="margin: 0;">ユーザーID: {{ Auth::user()->id }}</p>
    @else
        <p style="margin: 0;">ログインしていません。</p>
    @endif


{{ $attendance->date->format('n月j日') }}
defaultDate: "{{ $attendance->date->format('Y-m-d') }}"


    <div class="attendance-wrapper">
        <h1>勤怠詳細</h1>

        <form action="{{ route('attendance.update', $attendance->id) }}" method="POST">
            @csrf
            <!-- 名前 -->
            <div>
                <label for="name">名前:</label>
                <input type="text" id="name" name="name" value="{{ $user->name }}" readonly>
            </div>
            <!-- 日付1 -->
<div>
    <label for="date_year">日付（年）:</label>
    <div class="year-input-container">
        <input type="number" id="date_year" name="date_year" value="{{ $attendance->date->format('Y') }}" class="year-input" readonly>
        <span class="year-suffix">年</span>
    </div>
</div>

<div>
    <label for="date_month_day">日付（月日）:</label>
    <input type="text" id="date_month_day" name="date_month_day" value="{{ $attendance->date->format('n月j日') }}">
</div>



            <!-- 出勤時刻 -->
            <div>
                <label for="check_in">出勤時刻:</label>
                <input type="text" id="check_in" name="check_in" value="{{ $attendance->check_in }}">
            </div>
            <!-- 退勤時刻 -->
            <div>
                <label for="check_out">退勤時刻:</label>
                <input type="text" id="check_out" name="check_out" value="{{ $attendance->check_out }}">
            </div>
            <!-- 備考 -->
            <div>
                <label for="remarks">備考:</label>
                <input type="text" id="remarks" name="remarks" placeholder="修正内容を記載してください">
            </div>
            <!-- 修正申請ボタン -->
            <div>
                <button type="submit">修正申請</button>
            </div>
        </form>


    </div>


    </main>


    <script>
        // flatpicker による「日付（〇月〇日）」部の表示
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#date_month_day", {
                dateFormat: "n月j日", // 表示形式を設定
                locale: "ja",
                disableMobile: true
            });

            // 入力欄に初期値を設定
            const dateInput = document.getElementById("date_month_day");
            if (!dateInput.value) {
                dateInput.value = "{{ $attendance->date->format('n月j日') }}";
            }
        });

        // 日付「xxxx年」のポップアップ表示
        document.addEventListener('DOMContentLoaded', function() {
            const yearInput = document.getElementById('date_year');
            const currentYear = new Date().getFullYear();

            yearInput.addEventListener('click', function() {
                const popup = document.createElement('div');
                popup.className = 'year-popup';
                
                for (let year = currentYear - 10; year <= currentYear + 10; year++) {
                    const yearOption = document.createElement('div');
                    yearOption.textContent = year;
                    yearOption.addEventListener('click', function() {
                        yearInput.value = year;
                        document.body.removeChild(popup);
                    });
                    popup.appendChild(yearOption);
                }

                document.body.appendChild(popup);
                
                // ポップアップの位置を調整
                const rect = yearInput.getBoundingClientRect();
                popup.style.top = rect.bottom + 'px';
                popup.style.left = rect.left + 'px';
            });
        });
    </script>
</body>


</html>
