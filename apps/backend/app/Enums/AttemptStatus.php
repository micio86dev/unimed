<?php

declare(strict_types=1);

namespace App\Enums;

enum AttemptStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::Abandoned => 'Abandoned',
        };
    }

    public function isFinal(): bool
    {
        return $this === self::Completed || $this === self::Abandoned;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
