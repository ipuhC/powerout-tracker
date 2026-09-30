<?php

namespace App\Enums;

enum DurationType: string
{
    case FIXED = 'fixed';
    case UNTIL_CLOSED = 'until_closed';

    /**
     * Get all possible enum string values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Human-friendly label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::FIXED => 'Fixed Duration',
            self::UNTIL_CLOSED => 'Until Closed',
        };
    }

    /**
     * Determine if duration type is fixed.
     */
    public function isFixed(): bool
    {
        return $this === self::FIXED;
    }
}
