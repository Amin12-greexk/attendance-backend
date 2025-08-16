<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $clockInHour = $this->faker->numberBetween(8, 9);
        $clockInMinute = $this->faker->randomElement([0, 15, 30]);


        $clockOutHour = $clockInHour + 8;

        return [
            'name' => $this->faker->unique()->jobTitle(),
            'max_clock_in_time' => sprintf('%02d:%02d:00', $clockInHour, $clockInMinute),
            'max_clock_out_time' => sprintf('%02d:%02d:00', $clockOutHour, $clockInMinute),
        ];
    }
}