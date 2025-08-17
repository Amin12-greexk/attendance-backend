<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\AttendanceController;

// Rute ini hanya untuk contoh, bisa dihapus jika tidak dipakai
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Grup untuk API Versi 1
Route::prefix('v1')->group(function () {
    // Rute CRUD untuk Departments & Employees
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('employees', EmployeeController::class);

    // Rute khusus untuk Attendance
    Route::post('attendance/clock-in', [AttendanceController::class, 'clockIn']);
    Route::put('attendance/clock-out', [AttendanceController::class, 'clockOut']); // Pastikan tidak ada parameter {attendance}
    Route::get('attendance-log', [AttendanceController::class, 'getLog']);

    // Rute untuk mendapatkan status absensi terakhir karyawan
    Route::get('employees/{employee}/latest-attendance', [EmployeeController::class, 'getLatestAttendance']);

    Route::post('attendance/manual-entry', [AttendanceController::class, 'storeManual']);
});