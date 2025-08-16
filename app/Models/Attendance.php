<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'clock_in',
        'clock_out',
        'status',
        'lateness_minutes',
        'overtime_minutes',
        'early_leave_minutes',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function histories()
    {
        return $this->hasMany(AttendanceHistory::class);
    }
}
