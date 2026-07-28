<?php

namespace Tests\Feature\ReadingPlans;

use App\Enums\ReadingPlanStatus;
use App\Models\User;

class ReadingPlanManagementTest extends ReadingPlanTestCase
{
    // 所有者が未開始の読書計画を進行中へ変更できることを確認する。
    public function test_owner_can_start_a_not_started_plan(): void
    {
        $owner = User::factory()->create();
        $plan = $this->createReadingPlan($owner);

        $this->actingAs($owner)
            ->post(route('reading-plans.inprogress', $plan))
            ->assertRedirect(route('reading-plans.index'));

        $this->assertSame(ReadingPlanStatus::InProgress, $plan->refresh()->status);
        $this->assertNull($plan->completed_at);
    }

    // 所有者以外が読書計画を進行中へ変更できないことを確認する。
    public function test_non_owner_cannot_start_a_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = $this->createReadingPlan($owner);

        $this->actingAs($otherUser)
            ->post(route('reading-plans.inprogress', $plan))
            ->assertForbidden();

        $this->assertSame(ReadingPlanStatus::NotStarted, $plan->refresh()->status);
    }

    // 所有者が読書計画を読了状態にできることを確認する。
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

    // 所有者以外が読書計画を読了状態にできないことを確認する。
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

    // 所有者が読書計画を削除できることを確認する。
    public function test_owner_can_delete_a_plan(): void
    {
        $owner = User::factory()->create();
        $plan = $this->createReadingPlan($owner);

        $this->actingAs($owner)
            ->delete(route('reading-plans.destroy', $plan))
            ->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', ['id' => $plan->id]);
    }

    // 所有者以外が読書計画を削除できないことを確認する。
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

    // 期限切れ計画を期日変更なしで再開できないことを確認する。
    public function test_expired_plan_cannot_be_restarted_without_rescheduling(): void
    {
        $owner = User::factory()->create();
        $plan = $this->createReadingPlan($owner, [
            'target_date' => today()->subDay(),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $this->actingAs($owner)
            ->post(route('reading-plans.inprogress', $plan))
            ->assertForbidden();

        $this->assertSame(ReadingPlanStatus::Expired, $plan->refresh()->status);
    }
}
