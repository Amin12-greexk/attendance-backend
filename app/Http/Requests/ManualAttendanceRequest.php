<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|integer|exists:employees,id',
            'clock_in' => 'required|date_format:Y-m-d H:i:s',
            'clock_out' => 'nullable|date_format:Y-m-d H:i:s|after:clock_in',
        ];
    }
}