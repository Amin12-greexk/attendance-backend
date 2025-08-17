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

    /**
     * @OA\Post(
     * path="/api/v1/attendance/clock-in",
     * tags={"Attendance"},
     * summary="Clock in an employee",
     * @OA\RequestBody(
     * required=true,
     * description="Provide the employee ID to clock in",
     * @OA\JsonContent(
     * required={"employee_id"},
     * @OA\Property(property="employee_id", type="integer", format="int64", example=1)
     * )
     * ),
     * @OA\Response(response=201, description="Clock in successful"),
     * @OA\Response(response=422, description="Validation error"),
     * @OA\Response(response=400, description="Employee already clocked in today")
     * )
     */
    public function clockIn(ClockInRequest $request)
    {
        $existingAttendance = Attendance::where('employee_id', $request->employee_id)
            ->whereDate('clock_in', today())
            ->first();

        // Cek jika sudah ada absensi hari ini dan belum clock out
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

    /**
     * @OA\Put(
     * path="/api/v1/attendance/clock-out",
     * tags={"Attendance"},
     * summary="Clock out an employee",
     * @OA\RequestBody(
     * required=true,
     * description="Provide the employee ID to clock out",
     * @OA\JsonContent(
     * required={"employee_id"},
     * @OA\Property(property="employee_id", type="integer", format="int64", example=1)
     * )
     * ),
     * @OA\Response(response=200, description="Clock out successful"),
     * @OA\Response(response=404, description="No active attendance found to clock out"),
     * @OA\Response(response=422, description="Validation error")
     * )
     */
    public function clockOut(ClockOutRequest $request)
    {
        $attendance = Attendance::where('employee_id', $request->employee_id)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->whereDate('clock_in', today()) // Pastikan hanya untuk hari ini
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

    /**
     * @OA\Get(
     * path="/api/v1/attendance-log",
     * tags={"Attendance"},
     * summary="Get attendance log with filters",
     * @OA\Parameter(name="date", in="query", @OA\Schema(type="string", format="date", example="2025-08-15")),
     * @OA\Parameter(name="department_id", in="query", @OA\Schema(type="integer", example=1)),
     * @OA\Response(response=200, description="Successful operation"),
     * )
     */
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
        // Cari absensi yang ada pada tanggal clock_in untuk karyawan tersebut
        $attendance = Attendance::where('employee_id', $request->employee_id)
            ->whereDate('clock_in', Carbon::parse($request->clock_in)->toDateString())
            ->first();

        if ($attendance) {
            // Jika ada, update
            $attendance->update([
                'clock_in' => $request->clock_in,
                'clock_out' => $request->clock_out,
            ]);
        } else {
            // Jika tidak ada, buat baru
            $attendance = Attendance::create([
                'employee_id' => $request->employee_id,
                'clock_in' => $request->clock_in,
                'clock_out' => $request->clock_out,
                'status' => 'Pending',
            ]);
        }

        // Selalu hitung ulang statusnya
        $this->attendanceService->calculateAttendanceStatus($attendance);

        return response()->json(['message' => 'Manual attendance saved successfully', 'data' => $attendance]);
    }
}
