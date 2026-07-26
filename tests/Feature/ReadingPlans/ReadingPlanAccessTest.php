<?php

namespace Tests\Feature\ReadingPlans;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\User;

class ReadingPlanAccessTest extends ReadingPlanTestCase
{
    public function test_guest_is_redirected_to_login_from_index_and_create_pages(): void
    {
        $this->get(route('reading-plans.index'))
            ->assertRedirect(route('login'));

        $this->get(route('reading-plans.create'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_index_and_create_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reading-plans.index'))
            ->assertOk()
            ->assertViewIs('reading-plans.index')
            ->assertSee('読書計画');

        $this->actingAs($user)
            ->get(route('reading-plans.create'))
            ->assertOk()
            ->assertViewIs('reading-plans.create')
            ->assertSee('新規読書計画作成');
    }

    public function test_index_displays_only_the_authenticated_users_plans(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownBook = Book::factory()->create(['title' => '本人の計画書籍']);
        $otherBook = Book::factory()->create(['title' => '他ユーザーの計画書籍']);
        $ownPlan = $this->createReadingPlan($user, ['book_id' => $ownBook->id]);
        $this->createReadingPlan($otherUser, ['book_id' => $otherBook->id]);

        $this->actingAs($user)
            ->get(route('reading-plans.index'))
            ->assertOk()
            ->assertViewHas(
                'readingPlans',
                fn ($plans) => $plans->modelKeys() === [$ownPlan->id]
            )
            ->assertSee('本人の計画書籍')
            ->assertDontSee('他ユーザーの計画書籍');
    }

    public function test_index_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $notStartedPlan = $this->createReadingPlan($user, [
            'status' => ReadingPlanStatus::NotStarted,
        ]);
        $inProgressPlan = $this->createReadingPlan($user, [
            'status' => ReadingPlanStatus::InProgress,
        ]);
        $completedPlan = $this->createReadingPlan($user, [
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => today(),
        ]);

        $this->actingAs($user)
            ->get(route('reading-plans.index', [
                'status' => ReadingPlanStatus::InProgress->value,
            ]))
            ->assertOk()
            ->assertViewHas('currentStatus', ReadingPlanStatus::InProgress->value)
            ->assertViewHas(
                'readingPlans',
                fn ($plans) => $plans->modelKeys() === [$inProgressPlan->id]
                    && ! $plans->contains($notStartedPlan)
                    && ! $plans->contains($completedPlan)
            );
    }
}
