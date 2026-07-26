<?php

namespace Tests\Feature\ReadingPlans;

use App\Enums\ReadingPlanStatus;
use App\Models\User;

class ReadingPlanManagementTest extends ReadingPlanTestCase
{
    public function test_owner_can_complete_a_plan(): void
    {
        $this->travelTo('2026-07-26 12:00:00');
        $owner = User::factory()->create();
        $plan = $this->createReadingPlan($owner);

        $this->actingAs($owner)
            ->post(route('reading-plans.complete', $plan))
            ->assertRedirect(route('reading-plans.index'));

        $plan->refresh();

        $this->assertSame(ReadingPlanStatus::Completed, $plan->status);
        $this->assertSame('2026-07-26', $plan->completed_at->toDateString());
    }

    public function test_non_owner_cannot_complete_a_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = $this->createReadingPlan($owner);

        $this->actingAs($otherUser)
            ->post(route('reading-plans.complete', $plan))
            ->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::NotStarted->value,
            'completed_at' => null,
        ]);
    }

    public function test_owner_can_delete_a_plan(): void
    {
        $owner = User::factory()->create();
        $plan = $this->createReadingPlan($owner);

        $this->actingAs($owner)
            ->delete(route('reading-plans.destroy', $plan))
            ->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', ['id' => $plan->id]);
    }

    public function test_non_owner_cannot_delete_a_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = $this->createReadingPlan($owner);

        $this->actingAs($otherUser)
            ->delete(route('reading-plans.destroy', $plan))
            ->assertForbidden();

        $this->assertDatabaseHas('reading_plans', ['id' => $plan->id]);
    }
}
