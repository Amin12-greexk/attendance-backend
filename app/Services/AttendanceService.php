<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    public function calculateAttendanceStatus(Attendance $attendance): Attendance
    {
        $appTimezone = config('app.timezone', 'Asia/Jakarta');
        $department = $attendance->employee->department;

        // 0) Validasi rule minimal (tetap seperti logika kamu)
        if (!$department || !$department->max_clock_in_time) {
            $attendance->status = 'No Rule';
            $attendance->lateness_minutes = 0;
            $attendance->early_leave_minutes = 0;
            $attendance->overtime_minutes = 0;
            $attendance->save();
            return $attendance;
        }

        // 1) Parse clock in/out (pakai timezone app – sama seperti logika kamu)
        $clockInTime = Carbon::parse($attendance->clock_in, $appTimezone);
        $clockOutTime = $attendance->clock_out ? Carbon::parse($attendance->clock_out, $appTimezone) : null;

        // 2) Bangun patokan Max Clock In (tetap dengan try/catch seperti logika kamu)
        try {
            $inTimeString = Carbon::parse($department->max_clock_in_time)->format('H:i:s');
            $maxClockInStr = $clockInTime->toDateString() . ' ' . $inTimeString;
            $maxClockIn = Carbon::parse($maxClockInStr, $appTimezone);
        } catch (\Exception $e) {
            Log::error('Invalid time format for department ID ' . $department->id . ' (max_clock_in_time): ' . $department->max_clock_in_time);
            $attendance->status = 'Invalid Rule';
            $attendance->save();
            return $attendance;
        }

        // 3) (BARU) Bangun patokan Max Clock Out (opsional: kalau tersedia)
        $maxClockOut = null;
        if (!empty($department->max_clock_out_time)) {
            try {
                $outTimeString = Carbon::parse($department->max_clock_out_time)->format('H:i:s');
                $baseOutDate = $clockOutTime ? $clockOutTime->toDateString() : $clockInTime->toDateString();
                $maxClockOutStr = $baseOutDate . ' ' . $outTimeString;
                $maxClockOut = Carbon::parse($maxClockOutStr, $appTimezone);

                // Dukungan shift lintas hari (contoh: max_in 15:00, max_out 00:00 → next day)
                if ($maxClockOut->lessThanOrEqualTo($maxClockIn)) {
                    $maxClockOut->addDay();
                }
            } catch (\Exception $e) {
                Log::error('Invalid time format for department ID ' . $department->id . ' (max_clock_out_time): ' . $department->max_clock_out_time);
                $maxClockOut = null; // lanjut tanpa perhitungan early/overtime
            }
        }

        // --- LOGGING UNTUK DEBUGGING (dipertahankan & diperluas) ---
        Log::info('--- Attendance Calculation ---');
        Log::info('Employee ID: ' . $attendance->employee_id);
        Log::info('Clock In Time: ' . $clockInTime->toDateTimeString());
        if ($clockOutTime)
            Log::info('Clock Out Time: ' . $clockOutTime->toDateTimeString());
        Log::info('Max Clock In Time: ' . $maxClockIn->toDateTimeString());
        if ($maxClockOut)
            Log::info('Max Clock Out Time: ' . $maxClockOut->toDateTimeString());
        // -----------------------------------------------------------

        // 4) Reset nilai menit (tetap)
        $attendance->lateness_minutes = 0;
        $attendance->overtime_minutes = 0;
        $attendance->early_leave_minutes = 0;

        // 5) Hitung keterlambatan (tetap – prioritas logika kamu)
        if ($clockInTime->isAfter($maxClockIn)) {
            // pakai true agar absolut (positif)
            $attendance->lateness_minutes = $clockInTime->diffInMinutes($maxClockIn, true);
        }
        Log::info('Is After (Late?): ' . ($clockInTime->isAfter($maxClockIn) ? 'Yes' : 'No'));
        Log::info('Lateness (minutes): ' . $attendance->lateness_minutes);

        // 6) (BARU) Hitung Early Leave / Overtime jika clockOut & ada rule max out
        if ($clockOutTime && $maxClockOut) {
            if ($clockOutTime->lt($maxClockOut)) {
                $attendance->early_leave_minutes = $maxClockOut->diffInMinutes($clockOutTime); // positif
            } elseif ($clockOutTime->gt($maxClockOut)) {
                $attendance->overtime_minutes = $clockOutTime->diffInMinutes($maxClockOut);   // positif
            }
        }

        Log::info('Early Leave (minutes): ' . $attendance->early_leave_minutes);
        Log::info('Overtime (minutes): ' . $attendance->overtime_minutes);

        // 7) Status FINAL (tetap berbasis keterlambatan; jika tidak telat baru lihat out)
        if ($attendance->lateness_minutes > 0) {
            $attendance->status = 'Late'; // <- prioritas utama (sesuai permintaanmu)
        } else {
            if ($clockOutTime) {
                if ($attendance->early_leave_minutes > 0) {
                    $attendance->status = 'Early Leave';
                } elseif ($attendance->overtime_minutes > 0) {
                    $attendance->status = 'Overtime';
                } else {
                    $attendance->status = 'On Time';
                }
            } else {
                $attendance->status = 'In Office';
            }
        }

        Log::info('Final Status: ' . $attendance->status);
        Log::info('--------------------------');

        // 8) Simpan
        $attendance->save();

        return $attendance;
    }
}
