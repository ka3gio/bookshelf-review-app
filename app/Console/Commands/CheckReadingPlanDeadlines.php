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
     * 読書計画の期限状態を更新し、対象ユーザーへ通知する
     *
     * @return int コマンドの終了コード
     */
    public function handle(): int
    {
        return DB::transaction(fn () => $this->processDeadlines());
    }

    /**
     * 期限切れへの更新と期限前後の通知処理を実行する
     *
     * @return int コマンドの終了コード
     */
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

        $this->notifyPlansDueOn($today->addDays(3), 'three_days_before');
        $this->notifyPlansDueOn($today, 'on_due_date');
        $this->notifyPlansDueOn($today->subDays(3), 'three_days_after');

        return self::SUCCESS;
    }

    /**
     * 指定した期限日の読書計画へ通知を送信する
     *
     * @param  CarbonImmutable  $date  通知対象の期限日
     * @param  string  $timing  通知タイミング
     * @return void 戻り値なし
     */
    private function notifyPlansDueOn(CarbonImmutable $date, string $timing): void
    {
        ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', '!=', ReadingPlanStatus::Completed->value)
            ->whereDate('target_date', $date)
            ->chunkById(100, function ($plans) use ($timing): void {
                $plans->each(function (ReadingPlan $plan) use ($timing): void {
                    $this->notifyOnce($plan, $timing);
                });
            });
    }

    /**
     * 同じ日の重複送信を避けて期限通知を送信する
     *
     * @param  ReadingPlan  $plan  通知対象の読書計画
     * @param  string  $timing  通知タイミング
     * @return void 戻り値なし
     */
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
