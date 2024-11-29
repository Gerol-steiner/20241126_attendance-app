<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceStatusChange extends Model
{
    use HasFactory;

    protected $fillable = ['attendance_id', 'attendance_status_id', 'changed_at'];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function attendanceStatus()
    {
        return $this->belongsTo(AttendanceStatus::class);
    }
}
