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

    <div class="attendance-wrapper">
        <h1>勤怠詳細</h1>

            <form action="{{ route('attendance.update', $attendance->id) }}" method="POST" class="attendance-form">
                @csrf
                <table class="attendance-detail-table">
                    <!-- 1行目 -->
                    <tr>
                        <td class="col-1">名前</td>
                        <td class="col-2" colspan="4"><span id="name">{{ $user->name }}</span></td>
                    </tr>
                    <!-- 2行目 -->
                    <tr>
                        <td class="col-1">日付</td>
                        <td class="col-2">
                            <div class="year-input-container">
                                <input type="number" id="date_year" name="date_year" value="{{ $attendance->date->format('Y') }}" readonly>
                                <span class="year-suffix">年</span>
                            </div>
                        </td>
                        <td class="col-3"></td> <!-- 空欄 -->
                        <td class="col-4">
                            <input type="text" id="date_month_day" name="date_month_day" value="{{ $attendance->date->format('n月j日') }}">
                        </td>
                        <td class="col-5"></td> <!-- 空白の列 -->
                    </tr>
                    <!-- 3行目 -->
                    <tr>
                        <td class="col-1">出勤・退勤</td>
                        <td class="col-2">
                            <input type="time" id="check_in" name="check_in" value="{{ $attendance->check_in }}" class="time-input" >
                        </td>
                        <td class="col-3">
                            <span class="separator">～</span>
                        </td>
                        <td class="col-4">
                            <input type="time" id="check_out" name="check_out" value="{{ $attendance->check_out }}" class="time-input">
                        </td>
                        <td class="col-5"></td> <!-- 空白の列 -->
                    </tr>
                    <!-- 4行目 -->
                    @forelse ($attendance->breaktimes as $index => $breaktime)
                        <tr>
                            <td class="col-1">休憩{{ $index + 1 }}</td>
                            <td class="col-2">
                                <input type="time" name="breaktimes[{{ $breaktime->id }}][start]" value="{{ $breaktime->break_start }}" placeholder="休憩開始" class="time-input">
                            </td>
                            <td class="col-3">
                                <span class="separator">～</span>
                            </td>
                            <td class="col-4">
                                <input type="time" name="breaktimes[{{ $breaktime->id }}][end]" value="{{ $breaktime->break_end }}" placeholder="休憩終了" class="time-input">
                            </td>
                            <td class="col-5"></td> <!-- 空白の列 -->
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">休憩時間は登録されていません。</td> <!-- colspanを5に変更 -->
                        </tr>
                    @endforelse
                    <!-- 5行目 -->
                    <tr>
                        <td class="col-1">備考</td>
                        <td colspan="3" class="col-2">
                            <textarea id="remarks" name="remarks" placeholder="申請理由を記載してください"></textarea>
                        </td>
                        <td class="col-5"></td> <!-- 空白の列 -->
                    </tr>
                </table>


                <div class="button-container">
                    <button type="submit" class="submit-button">修正</button>
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
                popup.style.left = rect.left + 32 + 'px';
            });
        });
    </script>
</body>


</html>
