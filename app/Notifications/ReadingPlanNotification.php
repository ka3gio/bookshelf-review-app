<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReadingPlanNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly string $timing,
        private readonly ReadingPlan $readingPlan
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        [$title, $body] = match ($this->timing) {
            'three_days_before' => [
                '読了期日の3日前です',
                '『'.$this->readingPlan->book->title.'』の読了期日まであと3日です',
            ],
            'on_due_date' => [
                '読了期日は本日です',
                '『'.$this->readingPlan->book->title.'』は本日が読了期日です',
            ],
            'three_days_after' => [
                '読了期日から3日過ぎています',
                '『'.$this->readingPlan->book->title.'』の読了期日から3日過ぎています',
            ],
            default => ['不明', '不明な通知'],
        };

        return [
            'reading_plan_id' => $this->readingPlan->id,
            'timing' => $this->timing,
            'title' => $title,
            'body' => $body,
        ];
    }
}
