<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckReadingPlanDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-reading-plan-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '読書計画の期限を確認する';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        return DB::transaction(fn () => $this->processDeadlines());
    }

    private function processDeadlines(): int
    {
        $today = CarbonImmutable::today('Asia/Tokyo');

        ReadingPlan::query()
            ->whereIn('status', [
                ReadingPlanStatus::NotStarted->value,
                ReadingPlanStatus::InProgress->value,
            ])
            ->whereDate('target_date', '<', $today)
            ->update([
                'status' => ReadingPlanStatus::Expired->value,
            ]);

        ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', '!=', ReadingPlanStatus::Completed->value)
            ->whereDate('target_date', $today->addDays(3))
            ->chunkById(100, function ($plans) {
                foreach ($plans as $plan) {
                    $this->notifyOnce($plan, 'three_days_before');
                }
            });

        ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', '!=', ReadingPlanStatus::Completed->value)
            ->whereDate('target_date', $today)
            ->chunkById(100, function ($plans) {
                foreach ($plans as $plan) {
                    $this->notifyOnce($plan, 'on_due_date');
                }
            });

        ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', '!=', ReadingPlanStatus::Completed->value)
            ->whereDate('target_date', $today->subDays(3))
            ->chunkById(100, function ($plans) {
                foreach ($plans as $plan) {
                    $this->notifyOnce($plan, 'three_days_after');
                }
            });

        return self::SUCCESS;
    }

    private function notifyOnce(ReadingPlan $plan, string $timing): void
    {
        $today = CarbonImmutable::today('Asia/Tokyo');

        $alreadySentToday = $plan->user
            ->notifications()
            ->where('type', ReadingPlanNotification::class)
            ->where('data->reading_plan_id', $plan->id)
            ->where('data->timing', $timing)
            ->whereBetween('created_at', [
                $today->startOfDay()->utc(),
                $today->endOfDay()->utc(),
            ])
            ->exists();

        if (! $alreadySentToday) {
            $plan->user->notify(new ReadingPlanNotification($timing, $plan));
        }
    }
}
