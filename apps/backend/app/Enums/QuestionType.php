<?php

declare(strict_types=1);

namespace App\Enums;

enum QuestionType: string
{
    case Single = 'single';
    case Multiple = 'multiple';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Single choice',
            self::Multiple => 'Multiple choice',
        };
    }

    public function allowsMultiple(): bool
    {
        return $this === self::Multiple;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
