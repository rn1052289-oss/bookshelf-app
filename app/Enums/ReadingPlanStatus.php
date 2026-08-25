<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Expired = 'expired';

    /**
     * 画面表示用の状態名を返す。
     */
    public function label(): string
    {
        return match ($this) {
            self::InProgress => '読書中',
            self::Completed => '読了済み',
            self::Expired => '期限切れ',
        };
    }

    /**
     * 状態表示用のTailwind CSSクラスを返す。
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::InProgress => 'bg-blue-100 text-blue-800',
            self::Completed => 'bg-green-100 text-green-800',
            self::Expired => 'bg-red-100 text-red-800',
        };
    }
}
