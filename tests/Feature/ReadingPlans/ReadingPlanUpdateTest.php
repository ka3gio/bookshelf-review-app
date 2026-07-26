<?php

namespace Tests\Feature\ReadingPlans;

use App\Enums\ReadingPlanStatus;
use App\Models\User;

class ReadingPlanUpdateTest extends ReadingPlanTestCase
{
    public function test_owner_can_view_edit_page_with_existing_target_date(): void
    {
        $owner = User::factory()->create();
        $plan = $this->createReadingPlan($owner, [
            'target_date' => '2026-08-10',
        ]);

        $this->actingAs($owner)
            ->get(route('reading-plans.edit', $plan))
            ->assertOk()
            ->assertViewIs('reading-plans.edit')
            ->assertViewHas('readingPlan', fn ($readingPlan) => $readingPlan->is($plan))
            ->assertSee('value="2026-08-10"', false);
    }

    public function test_owner_can_update_target_date(): void
    {
        $owner = User::factory()->create();
        $plan = $this->createReadingPlan($owner);
        $newTargetDate = today()->addMonth()->toDateString();

        $this->actingAs($owner)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => $newTargetDate,
            ])
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(
            $newTargetDate,
            $plan->refresh()->target_date->toDateString()
        );
    }

    public function test_non_owner_cannot_edit_or_update_a_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = $this->createReadingPlan($owner);

        $this->actingAs($otherUser)
            ->get(route('reading-plans.edit', $plan))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => today()->addMonth()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_target_date_validation_rejects_missing_invalid_and_past_dates(): void
    {
        $this->travelTo('2026-07-26 12:00:00');
        $owner = User::factory()->create();
        $plan = $this->createReadingPlan($owner);
        $invalidCases = [
            [null, '期日を入力してください'],
            ['not-a-date', '期日を正しい形式で入力してください'],
            ['2026-07-25', '期限日は今日以降の日付を指定してください'],
        ];

        foreach ($invalidCases as [$targetDate, $message]) {
            $this->actingAs($owner)
                ->put(route('reading-plans.update', $plan), [
                    'target_date' => $targetDate,
                ])
                ->assertSessionHasErrors(['target_date' => $message]);
        }
    }

    public function test_completed_plan_cannot_be_edited_or_updated(): void
    {
        $owner = User::factory()->create();
        $plan = $this->createReadingPlan($owner, [
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => today(),
        ]);

        $this->actingAs($owner)
            ->get(route('reading-plans.edit', $plan))
            ->assertForbidden();

        $this->actingAs($owner)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => today()->addMonth()->toDateString(),
            ])
            ->assertForbidden();
    }
}
