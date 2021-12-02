<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{

    public function definition()
    {
        return [
            'title' => $this->faker->text(30),
            'description' => $this->faker->text(200),
            'image' => $this->faker->imageUrl,
            'price' => $this->faker->numberBetween(10, 100),
        ];
    }
}
