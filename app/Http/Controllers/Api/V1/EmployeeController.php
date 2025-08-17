<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\Response;

/**
 * @OA\Tag(
 * name="Employees",
 * description="API Endpoints for Employees"
 * )
 */
class EmployeeController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/v1/employees",
     * tags={"Employees"},
     * summary="Get list of employees",
     * @OA\Response(response=200, description="Successful operation"),
     * )
     */
    public function index()
    {
        // ✅ PERBAIKAN: Mengembalikan paginasi ke 10 sesuai permintaan
        return Employee::with('department')->paginate(10);
    }

    /**
     * @OA\Get(
     * path="/api/v1/employees/all",
     * tags={"Employees"},
     * summary="Get all employees with their latest attendance for today",
     * @OA\Response(response=200, description="Successful operation"),
     * )
     */
    public function all()
    {
        $employees = Employee::with('department')->get();
        $employees->load([
            'attendances' => function ($query) {
                $query->whereDate('clock_in', today())->latest();
            }
        ]);
        return response()->json($employees);
    }

    /**
     * @OA\Post(
     * path="/api/v1/employees",
     * tags={"Employees"},
     * summary="Create a new employee",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name", "email", "position", "department_id"},
     * @OA\Property(property="name", type="string", example="John Doe"),
     * @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     * @OA\Property(property="position", type="string", example="Software Engineer"),
     * @OA\Property(property="department_id", type="integer", example=1)
     * )
     * ),
     * @OA\Response(response=201, description="Employee created successfully"),
     * @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreEmployeeRequest $request)
    {
        $employee = Employee::create($request->validated());
        return response()->json($employee, Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     * path="/api/v1/employees/{id}",
     * tags={"Employees"},
     * summary="Get a single employee",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Successful operation"),
     * @OA\Response(response=404, description="Employee not found")
     * )
     */
    public function show(Employee $employee)
    {
        return $employee->load('department');
    }

    /**
     * @OA\Put(
     * path="/api/v1/employees/{id}",
     * tags={"Employees"},
     * summary="Update an employee",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name", "email", "position", "department_id"},
     * @OA\Property(property="name", type="string", example="John Doe"),
     * @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     * @OA\Property(property="position", type="string", example="Software Engineer"),
     * @OA\Property(property="department_id", type="integer", example=1)
     * )
     * ),
     * @OA\Response(response=200, description="Employee updated successfully"),
     * @OA\Response(response=404, description="Employee not found"),
     * @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $employee->update($request->validated());
        return response()->json($employee);
    }

    /**
     * @OA\Delete(
     * path="/api/v1/employees/{id}",
     * tags={"Employees"},
     * summary="Delete an employee",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=204, description="No content"),
     * @OA\Response(response=404, description="Employee not found")
     * )
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();
        return response()->noContent();
    }

    public function getLatestAttendance(Employee $employee)
    {
        $latestAttendance = $employee->attendances()
            ->whereDate('clock_in', today())
            ->latest()
            ->first();

        if (!$latestAttendance) {
            return response()->json(null);
        }

        return response()->json($latestAttendance);
    }
}
