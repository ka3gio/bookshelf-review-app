<?php

namespace Tests\Feature\ReadingPlans;

use App\Enums\ReadingPlanStatus;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProviderExternal;

class ReadingPlanUpdateTest extends ReadingPlanTestCase
{
    // 所有者が既存期日入りの編集画面を表示できることを確認する。
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

    // 所有者が読書計画の期日を更新できることを確認する。
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

    // 所有者以外が読書計画編集画面を表示できないことを確認する。
    public function test_non_owner_cannot_edit_a_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = $this->createReadingPlan($owner);

        $this->actingAs($otherUser)
            ->get(route('reading-plans.edit', $plan))
            ->assertForbidden();
    }

    // 所有者以外が読書計画を更新できないことを確認する。
    public function test_non_owner_cannot_update_a_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = $this->createReadingPlan($owner);

        $this->actingAs($otherUser)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => today()->addMonth()->toDateString(),
            ])
            ->assertForbidden();
    }

    // 期日の未入力、不正形式、過去日を拒否することを確認する。
    #[DataProviderExternal(ReadingPlanTestCase::class, 'invalidTargetDateCases')]
    public function test_target_date_validation_rejects_missing_invalid_and_past_dates(
        ?string $targetDate,
        string $message
    ): void {
        $this->travelTo('2026-07-26 12:00:00');
        $owner = User::factory()->create();
        $plan = $this->createReadingPlan($owner);
        $this->actingAs($owner)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => $targetDate,
            ])
            ->assertSessionHasErrors(['target_date' => $message]);
    }

    // 読了済み計画の編集画面を表示できないことを確認する。
    public function test_completed_plan_cannot_be_edited(): void
    {
        $owner = User::factory()->create();
        $plan = $this->createReadingPlan($owner, [
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => today(),
        ]);

        $this->actingAs($owner)
            ->get(route('reading-plans.edit', $plan))
            ->assertForbidden();
    }

    // 読了済み計画を更新できないことを確認する。
    public function test_completed_plan_cannot_be_updated(): void
    {
        $owner = User::factory()->create();
        $plan = $this->createReadingPlan($owner, [
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => today(),
        ]);

        $this->actingAs($owner)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => today()->addMonth()->toDateString(),
            ])
            ->assertForbidden();
    }

    // 期限切れ計画の期日更新時に進行中として再開することを確認する。
    public function test_updating_expired_plan_target_date_restarts_it_as_in_progress(): void
    {
        $this->travelTo('2026-07-26 12:00:00');
        $owner = User::factory()->create();
        $plan = $this->createReadingPlan($owner, [
            'target_date' => today()->subWeek(),
            'status' => ReadingPlanStatus::Expired,
        ]);
        $newTargetDate = today()->addWeek()->toDateString();

        $this->actingAs($owner)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => $newTargetDate,
            ])
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionDoesntHaveErrors();

        $plan->refresh();

        $this->assertSame(ReadingPlanStatus::InProgress, $plan->status);
        $this->assertSame($newTargetDate, $plan->target_date->toDateString());
        $this->assertNull($plan->completed_at);
    }
}
