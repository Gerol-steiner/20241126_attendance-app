<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠管理アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/admin/attendance_daily_list.css') }}" />

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





    <div class="attendance-wrapper">
        <h1>{{ $currentDate->year }}年{{ $currentDate->month }}月{{ $currentDate->day }}日の勤怠一覧</h1>
        <!-- 日付選択 -->
        <div class="date-navigation">
            <!-- 前日リンク -->
            <div class="day-link prev-day">
                <a href="{{ route('admin.attendance.daily_list', ['date' => $currentDate->copy()->subDay()->format('Y-m-d')]) }}" class="date-link">
                    <img src="{{ asset('images/arrow-left.svg') }}" alt="前日" class="arrow-icon"> 前日
                </a>
            </div>

            <!-- カレンダーアイコンと現在の日付 -->
            <span class="date-display">
                <img src="{{ asset('images/calendar_icon.svg') }}" alt="カレンダーアイコン" class="calendar-icon">
                {{ $currentDate->format('Y/m/d') }}
            </span>
            <!-- 翌日リンク（isTodayがtrueなら非表示） -->
            <div class="day-link next-day">
                @if (!$isToday)
                    <a href="{{ route('admin.attendance.daily_list', ['date' => $currentDate->copy()->addDay()->format('Y-m-d')]) }}" class="date-link">
                        翌日 <img src="{{ asset('images/arrow-right.svg') }}" alt="翌日" class="arrow-icon">
                    </a>
                @else
                    <!-- 空の要素 -->
                    <span></span>
                @endif
            </div>
        </div>

        <!-- 勤怠テーブル -->
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $entry)
                    <tr>
                        <td>{{ $entry['name'] }}</td>
                        <td>{{ $entry['check_in'] }}</td>
                        <td>{{ $entry['check_out'] }}</td>
                        <td>{{ $entry['breakTime'] }}</td>
                        <td>{{ $entry['totalTime'] }}</td>
                        <td>
                            @if ($entry['attendance_id'])
                                <a href="{{ route('attendance.detail', $entry['attendance_id']) }}">詳細</a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- CSV出力ボタン -->
        <div class="csv-button-wrapper">
            <button id="downloadCsv" class="csv-button">CSV出力</button>
        </div>

    </div>


    </main>

</html>
