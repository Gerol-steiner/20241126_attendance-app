<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceModification extends Model
{
    use HasFactory;

    /**
     * 書き込み可能なカラム
     *
     * @var array
     */
    protected $fillable = [
        'attendance_id',
        'user_id',
        'check_in',
        'check_out',
        'break_start',
        'break_end',
        'remarks',
        'approved_by',
        'approved_at',
    ];

    /**
     * 勤怠レコードとのリレーション
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 勤怠修正申請を行った申請者とのリレーション
     *
     * user_id カラムを通じて users テーブルと関連付け
     *勤怠修正を申請したユーザー（一般ユーザーまたは管理者）を示す。
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 修正を承認した管理者とのリレーション
     *
     * approved_by カラムを通じて users テーブルと関連付け
     * 勤怠修正申請を承認した管理者ユーザーを示す。
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
