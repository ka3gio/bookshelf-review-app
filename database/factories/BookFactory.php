<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->realText(20),
            'author' => fake()->name(),
            'isbn' => fake()->unique()->isbn13(),
            'published_date' => fake()->date(),
            'description' => fake()->optional()->passthrough(
                fake()->realText(28)
                . PHP_EOL
                . fake()->realText(120)
                . PHP_EOL
                . fake()->realText(55)
            ),
            'image_url' => fake()->optional()->imageUrl(),
        ];
    }
}
