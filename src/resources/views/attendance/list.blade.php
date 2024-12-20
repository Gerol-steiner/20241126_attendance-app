<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠管理アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance/list.css') }}" />

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

    <div class="attendance-wrapper">
        <h1>勤怠一覧</h1>
        <!-- 月の選択 -->
        <div class="month-navigation">
            <!-- 前月リンク -->
            <a href="{{ route('attendance.list', ['month' => $currentMonth->copy()->subMonth()->format('Y-m')]) }}" class="month-link">
                <img src="{{ asset('images/arrow-left.svg') }}" alt="前月" class="arrow-icon">
                前月
            </a>
            <!-- カレンダーアイコンと現在の月 -->
            <span class="month-display">
                <img src="{{ asset('images/calendar_icon.svg') }}" alt="カレンダーアイコン" class="calendar-icon">
                {{ mb_convert_kana($currentMonth->format('Y/m'), 'N') }}
            </span>
            <!-- 翌月リンク -->
            <a href="{{ route('attendance.list', ['month' => $currentMonth->copy()->addMonth()->format('Y-m')]) }}" class="month-link">
                翌月
                <img src="{{ asset('images/arrow-right.svg') }}" alt="翌月" class="arrow-icon">
            </a>
        </div>

        <!-- 勤怠テーブル -->
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $daysOfWeek = [
                        'Sun' => '日',
                        'Mon' => '月',
                        'Tue' => '火',
                        'Wed' => '水',
                        'Thu' => '木',
                        'Fri' => '金',
                        'Sat' => '土',
                    ];
                @endphp
                @foreach ($data as $entry)
                    <tr>
                        <!-- 日付と曜日 -->
                        <td>{{ $entry['date'] }} ({{ $daysOfWeek[$entry['day']] }})</td>
                        <!-- 出勤時間 -->
                        <td>{{ $entry['attendance'] && $entry['attendance']->check_in ? Carbon\Carbon::parse($entry['attendance']->check_in)->format('H:i') : '' }}</td>
                        <!-- 退勤時間 -->
                        <td>{{ $entry['attendance'] && $entry['attendance']->check_out ? Carbon\Carbon::parse($entry['attendance']->check_out)->format('H:i') : '' }}</td>
                        <!-- 休憩時間 -->
                        <td>{{ $entry['breakTime'] ?? '' }}</td>
                        <!-- 合計勤務時間 -->
                        <td>{{ $entry['totalTime'] ?? '' }}</td>
                        <!-- 詳細リンク -->
                        <td>
                            @if ($entry['latestModification'] && is_null($entry['latestModification']->approved_by))
                                <!-- 「承認待ち」の修正申請があれば表示させる -->
                                <a href="{{ route('attendance_modification.approve', ['attendance_correct_request' => $entry['latestModification']->id]) }}">詳細</a>
                            @elseif ($entry['attendance'])
                                <!-- 「承認待ち」の修正申請がなければ通常の「勤怠詳細」 -->
                                <a href="{{ route('attendance.detail', $entry['attendance']->id) }}">詳細</a>
                            @else
                                -
                            @endif
                        </td>
                @endforeach
            </tbody>
        </table>
    </div>


    </main>

</html>
