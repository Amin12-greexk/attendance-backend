<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\Response;

/**
 * @OA\Info(
 * version="1.0.0",
 * title="Attendance System API",
 * description="API Documentation for Attendance System"
 * )
 * @OA\Tag(
 * name="Departments",
 * description="API Endpoints for Departments"
 * )
 */
class DepartmentController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/v1/departments",
     * tags={"Departments"},
     * summary="Get list of departments",
     * @OA\Response(response=200, description="Successful operation"),
     * )
     */
    public function index()
    {
        return Department::paginate(10);
    }

    /**
     * @OA\Post(
     * path="/api/v1/departments",
     * tags={"Departments"},
     * summary="Create a new department",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name", "max_clock_in_time", "max_clock_out_time"},
     * @OA\Property(property="name", type="string", example="Engineering"),
     * @OA\Property(property="max_clock_in_time", type="string", format="time", example="09:00"),
     * @OA\Property(property="max_clock_out_time", type="string", format="time", example="17:00")
     * )
     * ),
     * @OA\Response(response=201, description="Department created successfully"),
     * @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreDepartmentRequest $request)
    {
        $department = Department::create($request->validated());
        return response()->json($department, Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     * path="/api/v1/departments/{id}",
     * tags={"Departments"},
     * summary="Get a single department",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Successful operation"),
     * @OA\Response(response=404, description="Department not found")
     * )
     */
    public function show(Department $department)
    {
        return $department;
    }

    /**
     * @OA\Put(
     * path="/api/v1/departments/{id}",
     * tags={"Departments"},
     * summary="Update a department",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name", "max_clock_in_time", "max_clock_out_time"},
     * @OA\Property(property="name", type="string", example="Engineering"),
     * @OA\Property(property="max_clock_in_time", type="string", format="time", example="09:00"),
     * @OA\Property(property="max_clock_out_time", type="string", format="time", example="17:00")
     * )
     * ),
     * @OA\Response(response=200, description="Department updated successfully"),
     * @OA\Response(response=404, description="Department not found"),
     * @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $department->update($request->validated());
        return response()->json($department);
    }

    /**
     * @OA\Delete(
     * path="/api/v1/departments/{id}",
     * tags={"Departments"},
     * summary="Delete a department",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=204, description="No content"),
     * @OA\Response(response=404, description="Department not found")
     * )
     */
    public function destroy(Department $department)
    {
        $department->delete();
        return response()->noContent();
    }
}