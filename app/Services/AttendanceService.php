<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceService
{
    public function calculateAttendanceStatus(Attendance $attendance): Attendance
    {
        $appTimezone = config('app.timezone');
        $department = $attendance->employee->department;

        if (!$department || !$department->max_clock_in_time || !$department->max_clock_out_time) {
            $attendance->save();
            return $attendance;
        }

        $clockInTime = Carbon::parse($attendance->clock_in, $appTimezone);
        $clockOutTime = $attendance->clock_out ? Carbon::parse($attendance->clock_out, $appTimezone) : null;

        // batas maksimal clock in & clock out pada hari clock in
        $maxClockIn = $clockInTime->copy()->startOfDay()->setTimeFromTimeString($department->max_clock_in_time);
        $maxClockOut = $clockInTime->copy()->startOfDay()->setTimeFromTimeString($department->max_clock_out_time);

        // kalau jam keluar lebih kecil dari jam masuk (shift malam), tambahkan 1 hari
        if ($maxClockOut->lessThan($maxClockIn)) {
            $maxClockOut->addDay();
        }

        // reset nilai
        $attendance->lateness_minutes = 0;
        $attendance->overtime_minutes = 0;
        $attendance->early_leave_minutes = 0;
        $attendance->status = null;

        // hitung keterlambatan
        if ($clockInTime->greaterThan($maxClockIn)) {
            $attendance->lateness_minutes = $clockInTime->diffInMinutes($maxClockIn);
        }

        // hitung pulang cepat / lembur
        if ($clockOutTime) {
            if ($clockOutTime->lessThan($maxClockOut)) {
                $attendance->early_leave_minutes = $maxClockOut->diffInMinutes($clockOutTime);
            } elseif ($clockOutTime->greaterThan($maxClockOut)) {
                $attendance->overtime_minutes = $clockOutTime->diffInMinutes($maxClockOut);
            }
        }

        // tentukan status
        if ($attendance->lateness_minutes > 0 && $attendance->early_leave_minutes > 0) {
            $attendance->status = 'Late & Early Leave';
        } elseif ($attendance->lateness_minutes > 0) {
            $attendance->status = 'Late';
        } elseif ($attendance->early_leave_minutes > 0) {
            $attendance->status = 'Early Leave';
        } elseif ($attendance->overtime_minutes > 0) {
            $attendance->status = 'Overtime';
        } else {
            $attendance->status = 'On Time';
        }

        $attendance->save();
        return $attendance;
    }
}
