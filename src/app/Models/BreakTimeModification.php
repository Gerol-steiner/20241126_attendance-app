<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTimeModification extends Model
{
    use HasFactory;

    /**
     * テーブル名
     */
    protected $table = 'break_time_modifications';

    /**
     * 一括代入可能な属性
     */
    protected $fillable = [
        'attendance_modification_id',
        'break_start',
        'break_end',
    ];

    /**
     * attendance_modificationとのリレーション
     */
    public function attendanceModification()
    {
        return $this->belongsTo(AttendanceModification::class, 'attendance_modification_id');
    }
}
