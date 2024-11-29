<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function attendanceStatusChanges()
    {
        return $this->hasMany(AttendanceStatusChange::class);
    }
}
