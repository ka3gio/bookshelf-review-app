<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = CarbonImmutable::today();

        $readingPlanData = [
            // 主要な動作確認は1人目のユーザーに集約する。
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784101010014',
                'status' => ReadingPlanStatus::NotStarted,
                'target_date' => $today->addDays(14),
                'completed_at' => null,
            ],
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784422100524',
                'status' => ReadingPlanStatus::NotStarted,
                'target_date' => $today,
                'completed_at' => null,
            ],
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784873115658',
                'status' => ReadingPlanStatus::InProgress,
                'target_date' => $today->addDays(7),
                'completed_at' => null,
            ],
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784863940246',
                'status' => ReadingPlanStatus::InProgress,
                'target_date' => $today->subDays(3),
                'completed_at' => null,
            ],
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784101010021',
                'status' => ReadingPlanStatus::Completed,
                'target_date' => $today->subDays(10),
                'completed_at' => $today->subDays(8),
            ],
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784309226712',
                'status' => ReadingPlanStatus::Completed,
                'target_date' => $today->subDays(5),
                'completed_at' => $today->subDays(7),
            ],

            // 他ユーザーにも状態や期日の異なる計画を配置し、一覧の分離と認可を確認できるようにする。
            [
                'user_email' => 'suzuki@example.com',
                'book_isbn' => '9784048930598',
                'status' => ReadingPlanStatus::NotStarted,
                'target_date' => $today->addDays(21),
                'completed_at' => null,
            ],
            [
                'user_email' => 'suzuki@example.com',
                'book_isbn' => '9784478025819',
                'status' => ReadingPlanStatus::InProgress,
                'target_date' => $today->subDay(),
                'completed_at' => null,
            ],
            [
                'user_email' => 'suzuki@example.com',
                'book_isbn' => '9784163902302',
                'status' => ReadingPlanStatus::Completed,
                'target_date' => $today->subDays(14),
                'completed_at' => $today->subDays(12),
            ],
            [
                'user_email' => 'tanaka@example.com',
                'book_isbn' => '9784822289607',
                'status' => ReadingPlanStatus::NotStarted,
                'target_date' => $today->addDays(30),
                'completed_at' => null,
            ],
            [
                'user_email' => 'tanaka@example.com',
                'book_isbn' => '9784822251468',
                'status' => ReadingPlanStatus::Completed,
                'target_date' => $today->subDays(3),
                'completed_at' => $today->subDays(4),
            ],
            [
                'user_email' => 'sato@example.com',
                'book_isbn' => '9784101010014',
                'status' => ReadingPlanStatus::InProgress,
                'target_date' => $today->addDays(2),
                'completed_at' => null,
            ],
            [
                'user_email' => 'sato@example.com',
                'book_isbn' => '9784873115658',
                'status' => ReadingPlanStatus::Completed,
                'target_date' => $today->subDays(20),
                'completed_at' => $today->subDays(20),
            ],
            [
                'user_email' => 'takahashi@example.com',
                'book_isbn' => '9784422100524',
                'status' => ReadingPlanStatus::NotStarted,
                'target_date' => $today->addDay(),
                'completed_at' => null,
            ],
            [
                'user_email' => 'takahashi@example.com',
                'book_isbn' => '9784863940246',
                'status' => ReadingPlanStatus::InProgress,
                'target_date' => $today->subDays(7),
                'completed_at' => null,
            ],
            [
                'user_email' => 'takahashi@example.com',
                'book_isbn' => '9784309226712',
                'status' => ReadingPlanStatus::Completed,
                'target_date' => $today->subDays(30),
                'completed_at' => $today->subDays(25),
            ],
        ];

        foreach ($readingPlanData as $planData) {
            $user = User::query()
                ->where('email', $planData['user_email'])
                ->firstOrFail();
            $book = Book::query()
                ->where('isbn', $planData['book_isbn'])
                ->firstOrFail();

            ReadingPlan::updateOrCreate(
                [
                    'user_id' => $user->getKey(),
                    'book_id' => $book->getKey(),
                ],
                [
                    'status' => $planData['status'],
                    'target_date' => $planData['target_date'],
                    'completed_at' => $planData['completed_at'],
                ]
            );
        }
    }
}
