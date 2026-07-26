<?php

namespace Tests\Feature\Notifications;

use App\Enums\ReadingPlanStatus;
use App\Models\User;
use App\Notifications\ReadingPlanNotification;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use RuntimeException;

class ReadingReminderCommandTest extends NotificationTestCase
{
    public function test_command_stores_database_notifications_only_for_eligible_plans(): void
    {
        $this->travelTo('2026-07-26 07:00:00');
        $user = User::factory()->create();
        $threeDaysBefore = $this->createReadingPlan($user, [
            'target_date' => today()->addDays(3),
        ]);
        $onDueDate = $this->createReadingPlan($user, [
            'target_date' => today(),
        ]);
        $threeDaysAfter = $this->createReadingPlan($user, [
            'target_date' => today()->subDays(3),
        ]);
        $this->createReadingPlan($user, [
            'target_date' => today()->addDays(2),
        ]);
        $this->createReadingPlan($user, [
            'target_date' => today()->addDays(3),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => today(),
        ]);

        $this->artisan('app:check-reading-plan-deadlines')
            ->assertSuccessful();

        $notifications = $user->notifications()->get();
        $actualTimings = $notifications
            ->mapWithKeys(fn ($notification) => [
                (int) $notification->data['reading_plan_id'] => $notification->data['timing'],
            ])
            ->all();
        $expectedTimings = [
            $threeDaysBefore->id => 'three_days_before',
            $onDueDate->id => 'on_due_date',
            $threeDaysAfter->id => 'three_days_after',
        ];
        ksort($actualTimings);
        ksort($expectedTimings);

        $this->assertCount(3, $notifications);
        $this->assertSame($expectedTimings, $actualTimings);
        $this->assertTrue(
            $notifications->every(
                fn ($notification) => $notification->type === ReadingPlanNotification::class
                    && $notification->read_at === null
            )
        );
        $this->assertDatabaseCount('notifications', 3);
        $this->assertDatabaseHas('notifications', [
            'type' => ReadingPlanNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);
    }

    public function test_command_does_not_create_duplicate_reminder_for_same_plan_on_same_day(): void
    {
        $this->travelTo('2026-07-26 07:00:00');
        $user = User::factory()->create();
        $plan = $this->createReadingPlan($user, [
            'target_date' => today()->addDays(3),
        ]);

        $this->artisan('app:check-reading-plan-deadlines')->assertSuccessful();
        $this->artisan('app:check-reading-plan-deadlines')->assertSuccessful();

        $this->assertSame(
            1,
            $user->notifications()
                ->where('data->reading_plan_id', $plan->id)
                ->count()
        );
    }

    public function test_command_marks_overdue_active_plans_as_expired(): void
    {
        $this->travelTo('2026-07-26 07:00:00');
        $user = User::factory()->create();
        $overdueNotStarted = $this->createReadingPlan($user, [
            'target_date' => today()->subDay(),
        ]);
        $overdueInProgress = $this->createReadingPlan($user, [
            'target_date' => today()->subDays(2),
            'status' => ReadingPlanStatus::InProgress,
        ]);
        $dueToday = $this->createReadingPlan($user, [
            'target_date' => today(),
        ]);
        $completed = $this->createReadingPlan($user, [
            'target_date' => today()->subDay(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => today()->subDays(2),
        ]);

        $this->artisan('app:check-reading-plan-deadlines')
            ->assertSuccessful();

        $this->assertSame(ReadingPlanStatus::Expired, $overdueNotStarted->refresh()->status);
        $this->assertSame(ReadingPlanStatus::Expired, $overdueInProgress->refresh()->status);
        $this->assertSame(ReadingPlanStatus::NotStarted, $dueToday->refresh()->status);
        $this->assertSame(ReadingPlanStatus::Completed, $completed->refresh()->status);
    }

    public function test_command_rolls_back_database_changes_when_related_processing_fails(): void
    {
        $this->travelTo('2026-07-26 07:00:00');
        $user = User::factory()->create();
        $overduePlan = $this->createReadingPlan($user, [
            'target_date' => today()->subDay(),
        ]);
        $this->createReadingPlan($user, [
            'target_date' => today()->addDays(3),
        ]);
        $this->createReadingPlan($user, [
            'target_date' => today()->addDays(3),
        ]);
        $sendingCount = 0;

        Event::listen(
            NotificationSending::class,
            function () use (&$sendingCount): void {
                $sendingCount++;

                if ($sendingCount === 2) {
                    throw new RuntimeException('通知処理に失敗しました');
                }
            }
        );

        try {
            Artisan::call('app:check-reading-plan-deadlines');
            $this->fail('関連処理の例外が送出されませんでした');
        } catch (RuntimeException $exception) {
            $this->assertSame('通知処理に失敗しました', $exception->getMessage());
        } finally {
            Event::forget(NotificationSending::class);
        }

        $this->assertSame(2, $sendingCount);
        $this->assertSame(
            ReadingPlanStatus::NotStarted,
            $overduePlan->refresh()->status
        );
        $this->assertDatabaseCount('notifications', 0);
    }
}
