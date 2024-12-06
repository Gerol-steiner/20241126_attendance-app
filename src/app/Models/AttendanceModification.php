<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceModification extends Model
{
    use HasFactory;

    /**
     * テーブル名
     */
    protected $table = 'attendance_modifications';

    /**
     * 一括代入可能な属性
     */
    protected $fillable = [
        'user_id',
        'attendance_id',
        'check_in',
        'check_out',
        'remark',
        'approved_by',
    ];

    /**
     * この修正を作成したユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この修正が関連付けられている勤怠レコード
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * この勤怠修正に関連する休憩時間修正
     */
    public function breakTimeModifications()
    {
        return $this->hasMany(BreakTimeModification::class, 'attendance_modification_id');
    }

    /**
     * この修正を承認した管理者ユーザー
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

