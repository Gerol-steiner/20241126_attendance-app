<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠管理アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/admin/staff_monthly_attendance.css') }}" />
</head>

<body>
    <header class="header">
        <div class="header_inner">
            <a class="header__logo" href="/admin/staff/list">
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

    <!--開発用-->
    @if (Auth::check())
        <p style="margin: 0;">ユーザーID: {{ Auth::user()->id }}</p>
    @else
        <p style="margin: 0;">ログインしていません。</p>
    @endif


    <main>
        <div class="attendance-wrapper">
            <h1>{{ $user->name }}さんの月次勤怠一覧</h1>
            <div class="month-navigation">
                <a href="{{ route('admin.attendance.staff.monthly_list', ['id' => $user->id, 'month' => $currentMonth->copy()->subMonth()->format('Y-m')]) }}" class="month-link">
                    <img src="{{ asset('images/arrow-left.svg') }}" alt="前月" class="arrow-icon">
                    前月
                </a>
                <span class="month-display">
                    <img src="{{ asset('images/calendar_icon.svg') }}" alt="カレンダーアイコン" class="calendar-icon">
                    {{ mb_convert_kana($currentMonth->format('Y/m'), 'N') }}
                </span>
                <a href="{{ route('admin.attendance.staff.monthly_list', ['id' => $user->id, 'month' => $currentMonth->copy()->addMonth()->format('Y-m')]) }}" class="month-link">
                    翌月
                    <img src="{{ asset('images/arrow-right.svg') }}" alt="翌月" class="arrow-icon">
                </a>
            </div>

            <table class="attendance-table" id="attendanceTable">
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
                            @if ($entry['attendance'])
                                <a href="{{ route('attendance.detail', $entry['attendance']->id) }}">詳細</a>
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
                <button id="downloadCsv" class="csv-button">ＣＳＶ出力</button>
            </div>

        </div>
    </main>

    <script>
        document.getElementById('downloadCsv').addEventListener('click', function () {
            const table = document.getElementById('attendanceTable');
            let csvContent = "";

            // テーブルヘッダーをCSVに追加
            const headers = Array.from(table.querySelectorAll('thead tr th'))
                .map(th => `"${th.textContent.trim()}"`)
                .join(',');
            csvContent += headers + '\n';

            // テーブルデータをCSVに追加
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            rows.forEach(row => {
                const rowData = Array.from(row.querySelectorAll('td'))
                    .map(td => `"${td.textContent.trim()}"`)
                    .join(',');
                csvContent += rowData + '\n';
            });

            // CSVデータをダウンロード
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.setAttribute('download', 'attendance_list.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    </script>

</body>

</html>
