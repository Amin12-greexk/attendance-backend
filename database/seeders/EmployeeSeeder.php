<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        Employee::create([
            'department_id' => 1,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'position' => 'Software Engineer',
        ]);
    }
}