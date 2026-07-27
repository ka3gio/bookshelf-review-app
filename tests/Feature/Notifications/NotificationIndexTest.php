<?php

namespace Tests\Feature\Notifications;

use App\Models\Book;
use App\Models\User;
use App\Notifications\ReadingPlanNotification;

class NotificationIndexTest extends NotificationTestCase
{
    // ゲストによる通知一覧へのアクセスを拒否することを確認する。
    public function test_guest_cannot_view_notifications(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));
    }

    // ゲストによる通知の既読操作を拒否することを確認する。
    public function test_guest_cannot_mark_a_notification_as_read(): void
    {
        $user = User::factory()->create();
        $plan = $this->createReadingPlan($user);
        $user->notify(new ReadingPlanNotification('three_days_before', $plan));
        $notification = $user->notifications()->firstOrFail();

        $this->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('login'));

        $this->assertNull($notification->refresh()->read_at);
    }

    // ログイン中ユーザー自身の通知だけを表示することを確認する。
    public function test_index_displays_only_the_authenticated_users_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownBook = Book::factory()->create(['title' => '本人への通知書籍']);
        $otherBook = Book::factory()->create(['title' => '他ユーザーへの通知書籍']);
        $ownPlan = $this->createReadingPlan($user, ['book_id' => $ownBook->id]);
        $otherPlan = $this->createReadingPlan($otherUser, ['book_id' => $otherBook->id]);
        $user->notify(new ReadingPlanNotification('three_days_before', $ownPlan));
        $otherUser->notify(new ReadingPlanNotification('three_days_before', $otherPlan));
        $ownNotification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertViewIs('notifications.index')
            ->assertViewHas(
                'notifications',
                fn ($notifications) => $notifications->modelKeys() === [$ownNotification->id]
            )
            ->assertSee('本人への通知書籍')
            ->assertDontSee('他ユーザーへの通知書籍');
    }

    // 自分の通知を既読にできることを確認する。
    public function test_authenticated_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();
        $plan = $this->createReadingPlan($user);
        $user->notify(new ReadingPlanNotification('three_days_before', $plan));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->from(route('notifications.index'))
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull($notification->refresh()->read_at);
    }

    // 他ユーザーの通知を既読にできないことを確認する。
    public function test_authenticated_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherPlan = $this->createReadingPlan($otherUser);
        $otherUser->notify(new ReadingPlanNotification('three_days_before', $otherPlan));
        $notification = $otherUser->notifications()->firstOrFail();

        $this->actingAs($user)
            ->post(route('notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->refresh()->read_at);
    }
}
