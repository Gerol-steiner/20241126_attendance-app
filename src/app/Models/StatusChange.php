<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusChange extends Model
{
    use HasFactory;

    protected $fillable = ['attendance_id', 'status_id', 'changed_at'];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function Status()
    {
        return $this->belongsTo(Status::class);
    }
}
