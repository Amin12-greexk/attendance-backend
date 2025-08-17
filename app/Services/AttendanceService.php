<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceService
{
    public function calculateAttendanceStatus(Attendance $attendance): Attendance
    {
        $appTimezone = config('app.timezone', 'Asia/Jakarta');
        $department = $attendance->employee->department;

        // Jika karyawan tidak memiliki departemen atau departemen tidak punya aturan, hentikan proses.
        if (!$department || !$department->max_clock_in_time || !$department->max_clock_out_time) {
            $attendance->status = 'No Rule';
            $attendance->save();
            return $attendance;
        }

        // 1. Parse semua waktu yang relevan dan pastikan zona waktunya konsisten.
        $clockInTime = Carbon::parse($attendance->clock_in, $appTimezone);
        $clockOutTime = $attendance->clock_out ? Carbon::parse($attendance->clock_out, $appTimezone) : null;

        // 2. Buat waktu acuan (max_in dan max_out) pada tanggal yang sama dengan tanggal clock_in.
        // Ini adalah cara paling andal untuk menghindari bug antar hari atau zona waktu.
        $referenceDate = $clockInTime->toDateString();
        $maxClockIn = Carbon::createFromFormat('Y-m-d H:i:s', $referenceDate . ' ' . $department->max_clock_in_time, $appTimezone);
        $maxClockOut = Carbon::createFromFormat('Y-m-d H:i:s', $referenceDate . ' ' . $department->max_clock_out_time, $appTimezone);

        // 3. Reset semua nilai menit sebelum menghitung ulang.
        $attendance->lateness_minutes = 0;
        $attendance->overtime_minutes = 0;
        $attendance->early_leave_minutes = 0;

        // 4. Hitung keterlambatan.
        if ($clockInTime->isAfter($maxClockIn)) {
            $attendance->lateness_minutes = $clockInTime->diffInMinutes($maxClockIn);
        }

        // 5. Hitung pulang cepat atau lembur (hanya jika sudah clock out).
        if ($clockOutTime) {
            if ($clockOutTime->isBefore($maxClockOut)) {
                $attendance->early_leave_minutes = $maxClockOut->diffInMinutes($clockOutTime);
            } elseif ($clockOutTime->isAfter($maxClockOut)) {
                $attendance->overtime_minutes = $clockOutTime->diffInMinutes($maxClockOut);
            }
        }

        // 6. Tentukan Status FINAL berdasarkan nilai menit yang sudah dihitung.
        $isLate = $attendance->lateness_minutes > 0;
        $isEarly = $attendance->early_leave_minutes > 0;
        $isOvertime = $attendance->overtime_minutes > 0;

        $statusParts = [];
        if ($isLate) {
            $statusParts[] = 'Late';
        }

        // Lembur dan Pulang Cepat adalah kondisi yang saling eksklusif.
        if ($isEarly) {
            $statusParts[] = 'Early Leave';
        } elseif ($isOvertime) {
            $statusParts[] = 'Overtime';
        }

        if (empty($statusParts)) {
            // Hanya On Time jika sudah clock out dan tidak ada penyimpangan.
            $attendance->status = $clockOutTime ? 'On Time' : 'In Office';
        } else {
            $attendance->status = implode(' & ', $statusParts);
        }

        $attendance->save();
        return $attendance;
    }
}
