<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class SchoolFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'user_name' => $this->faker->unique()->userName(),
            'password' => Hash::make('password'),
        ];
    }
}
