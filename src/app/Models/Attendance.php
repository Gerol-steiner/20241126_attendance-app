<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    /**
     * テーブル名
     *
     * @var string
     */
    protected $table = 'attendances';

    /**
     * 書き込み可能なカラム
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'date',
        'check_in',
        'check_out',
        'break_start',
        'break_end',
        'remarks',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceStatusChanges()
    {
        return $this->hasMany(AttendanceStatusChange::class);
    }
}
