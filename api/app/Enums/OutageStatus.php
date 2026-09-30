<?php

namespace App\Enums;

enum OutageStatus: string
{
    case ACTIVE = 'active';
    case CLOSED = 'closed';
    case EXPIRED = 'expired';

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
            self::ACTIVE => 'Active',
            self::CLOSED => 'Closed',
            self::EXPIRED => 'Expired',
        };
    }

    /**
     * Determine if status is currently active.
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
