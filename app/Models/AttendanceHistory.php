<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_id',
        'action',
        'action_time',
    ];

    /**
     * Get the attendance record associated with the history.
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}