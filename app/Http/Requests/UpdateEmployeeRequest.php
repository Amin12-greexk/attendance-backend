<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')->id;
        return [
            'department_id' => 'required|integer|exists:departments,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:employees,email,' . $employeeId,
            'position' => 'required|string|max:255',
        ];
    }
}