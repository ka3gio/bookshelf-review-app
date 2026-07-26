<?php

namespace Tests\Feature\ReadingPlans;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;

class ReadingPlanStoreTest extends ReadingPlanTestCase
{
    public function test_authenticated_user_can_create_a_plan_for_a_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $targetDate = today()->addMonth()->toDateString();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => $targetDate,
            ]);

        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionDoesntHaveErrors();
        $plan = ReadingPlan::whereBelongsTo($user)
            ->whereBelongsTo($book)
            ->firstOrFail();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::NotStarted->value,
            'completed_at' => null,
        ]);
        $this->assertSame($targetDate, $plan->target_date->toDateString());
    }
}
