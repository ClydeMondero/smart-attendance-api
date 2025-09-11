<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Student;

class StudentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Student::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'barcode' => fake()->regexify('[A-Za-z0-9]{50}'),
            'full_name' => fake()->regexify('[A-Za-z0-9]{191}'),
            'grade_level' => fake()->regexify('[A-Za-z0-9]{50}'),
            'section' => fake()->regexify('[A-Za-z0-9]{50}'),
            'parent_contact' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
