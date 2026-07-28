<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
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
                .PHP_EOL
                .fake()->realText(120)
                .PHP_EOL
                .fake()->realText(55)
            ),
            'image_url' => fake()->optional()->imageUrl(),
        ];
    }
}
