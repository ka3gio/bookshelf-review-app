<?php

namespace App\Enums;

enum ReadingPlanStatus: int
{
    case NotStarted = 1;
    case InProgress = 2;
    case Completed = 3;
    case Expired = 4;

    /**
     * ステータスの表示名を返す
     *
     * @return string ステータスの表示名
     */
    public function label(): string
    {
        return match ($this) {
            self::NotStarted => '未着手',
            self::InProgress => '進行中',
            self::Completed => '読了',
            self::Expired => '期限切れ',
        };
    }

    /**
     * ステータスに対応するバッジのCSSクラスを返す
     *
     * @return string バッジのCSSクラス
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::NotStarted => 'bg-gray-100 text-gray-800',
            self::InProgress => 'bg-blue-100 text-blue-800',
            self::Completed => 'bg-green-100 text-green-800',
            self::Expired => 'bg-red-100 text-red-800',
        };
    }
}
