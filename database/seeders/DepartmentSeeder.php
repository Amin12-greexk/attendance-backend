<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        Department::create([
            'name' => 'Technology',
            'max_clock_in_time' => '09:00:00',
            'max_clock_out_time' => '17:00:00',
        ]);
        Department::create([
            'name' => 'Human Resources',
            'max_clock_in_time' => '08:30:00',
            'max_clock_out_time' => '16:30:00',
        ]);
    }
}