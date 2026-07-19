<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceNotFoundTest extends TestCase
{
    use RefreshDatabase;

    public function test_nonexistent_book_returns_not_found(): void
    {
        $this->get(route('books.show', 999999))
            ->assertNotFound();
    }

    public function test_nonexistent_review_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reviews.edit', 999999))
            ->assertNotFound();
    }

    public function test_nonexistent_genre_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('genres.show', 999999))
            ->assertNotFound();
    }
}
