<?php

namespace Tests\Feature\ReadingPlans;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;

class ReadingPlanAccessTest extends ReadingPlanTestCase
{
    // ゲストによる読書計画画面へのアクセスを拒否することを確認する。
    #[DataProvider('readingPlanScreenCases')]
    public function test_guest_is_redirected_to_login_from_reading_plan_pages(string $routeName): void
    {
        $this->get(route($routeName))
            ->assertRedirect(route('login'));
    }

    // 認証済みユーザーが読書計画画面を表示できることを確認する。
    #[DataProvider('readingPlanScreenCases')]
    public function test_authenticated_user_can_view_reading_plan_pages(string $routeName): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertOk();
    }

    // ゲストによる読書計画の編集・更新・削除・状態変更を拒否することを確認する。
    #[DataProvider('readingPlanMutationCases')]
    public function test_guest_cannot_mutate_reading_plans(
        string $method,
        string $routeName
    ): void {
        $owner = User::factory()->create();
        $plan = $this->createReadingPlan($owner);
        $url = route($routeName, $plan);
        $payload = ['target_date' => today()->addMonth()->toDateString()];

        $response = match ($method) {
            'get' => $this->get($url),
            'put' => $this->put($url, $payload),
            'delete' => $this->delete($url),
            'post' => $this->post($url),
        };

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::NotStarted->value,
        ]);
    }

    // ログイン中ユーザー自身の読書計画だけを表示することを確認する。
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

    // 読書計画をステータスで絞り込めることを確認する。
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

    // 読書計画一覧の不正なステータス指定を拒否することを確認する。
    #[DataProvider('invalidStatusCases')]
    public function test_index_rejects_invalid_status_filter(mixed $status): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reading-plans.index', ['status' => $status]))
            ->assertRedirect()
            ->assertSessionHasErrors([
                'status' => '状態の指定が正しくありません',
            ]);
    }

    // 読了ボタンは進行中の読書計画にだけ表示されることを確認する。
    public function test_index_displays_complete_button_only_for_in_progress_plans(): void
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

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index'));

        $response
            ->assertOk()
            ->assertSee(route('reading-plans.complete', $inProgressPlan), false)
            ->assertDontSee(route('reading-plans.complete', $notStartedPlan), false)
            ->assertDontSee(route('reading-plans.complete', $completedPlan), false);
    }

    public static function readingPlanScreenCases(): array
    {
        return [
            'index' => ['reading-plans.index'],
            'create' => ['reading-plans.create'],
        ];
    }

    public static function readingPlanMutationCases(): array
    {
        return [
            'edit' => ['get', 'reading-plans.edit'],
            'update' => ['put', 'reading-plans.update'],
            'delete' => ['delete', 'reading-plans.destroy'],
            'start' => ['post', 'reading-plans.inprogress'],
            'complete' => ['post', 'reading-plans.complete'],
        ];
    }

    public static function invalidStatusCases(): array
    {
        return [
            '文字列' => ['invalid'],
            '定義外の数値' => [99],
            'ゼロ' => [0],
        ];
    }
}
