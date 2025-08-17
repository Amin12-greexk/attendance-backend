<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClockInRequest;
use App\Http\Requests\ClockOutRequest;
use App\Models\Attendance;
use App\Models\AttendanceHistory;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\ManualAttendanceRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log; // <-- TAMBAHKAN INI

/**
 * @OA\Tag(
 * name="Attendance",
 * description="API Endpoints for Employee Attendance"
 * )
 */
class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    // ... (fungsi clockIn, clockOut, getLog tidak berubah) ...

    public function clockIn(ClockInRequest $request)
    {
        $existingAttendance = Attendance::where('employee_id', $request->employee_id)
            ->whereDate('clock_in', today())
            ->first();

        if ($existingAttendance && $existingAttendance->clock_in && !$existingAttendance->clock_out) {
            return response()->json(['message' => 'Employee has already clocked in today and not clocked out.'], Response::HTTP_BAD_REQUEST);
        }

        $attendance = Attendance::create([
            'employee_id' => $request->employee_id,
            'clock_in' => now(),
            'status' => 'Pending',
        ]);

        AttendanceHistory::create([
            'attendance_id' => $attendance->id,
            'action' => 'CLOCK_IN',
            'action_time' => $attendance->clock_in,
        ]);

        $this->attendanceService->calculateAttendanceStatus($attendance);

        return response()->json(['message' => 'Clock in successful', 'data' => $attendance], Response::HTTP_CREATED);
    }

    public function clockOut(ClockOutRequest $request)
    {
        $attendance = Attendance::where('employee_id', $request->employee_id)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->whereDate('clock_in', today())
            ->latest('clock_in')
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'No active attendance found to clock out.'], Response::HTTP_NOT_FOUND);
        }

        $attendance->update(['clock_out' => now()]);

        AttendanceHistory::create([
            'attendance_id' => $attendance->id,
            'action' => 'CLOCK_OUT',
            'action_time' => $attendance->clock_out,
        ]);

        $this->attendanceService->calculateAttendanceStatus($attendance);

        return response()->json(['message' => 'Clock out successful', 'data' => $attendance]);
    }

    public function getLog(Request $request)
    {
        $query = Attendance::with('employee.department');

        if ($request->filled('date')) {
            $query->whereDate('clock_in', $request->date);
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        $logs = $query->latest()->paginate(15);

        return response()->json($logs);
    }

    public function storeManual(ManualAttendanceRequest $request)
    {
        $clockIn = Carbon::parse($request->clock_in, 'Asia/Jakarta')->setTimezone('UTC');
        $clockOut = $request->clock_out
            ? Carbon::parse($request->clock_out, 'Asia/Jakarta')->setTimezone('UTC')
            : null;

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $request->employee_id,
                'clock_in' => $clockIn,
            ],
            [
                'clock_out' => $clockOut,
                'status' => 'Pending',
            ]
        );

        $this->attendanceService->calculateAttendanceStatus($attendance);

        return response()->json([
            'message' => 'Manual attendance saved successfully',
            'data' => $attendance
        ]);
    }

}
