<?php

namespace App\Enums;

enum TaskStatus: string
{
    case NEW              = 'NEW';
    case PENDING_PAYMENT  = 'PENDING_PAYMENT';
    case PAID             = 'PAID';
    case ASSIGNED         = 'ASSIGNED';
    case IN_PROGRESS      = 'IN_PROGRESS';
    case COMPLETED        = 'COMPLETED';
    case CANCELLED        = 'CANCELLED';

    /**
     * Get all status values as an array (for validation rules).
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Statuses that a customer can delete/cancel their own task from.
     */
    public static function cancellableByCustomer(): array
    {
        return [
            self::NEW->value,
            self::PENDING_PAYMENT->value,
        ];
    }

    /**
     * Terminal statuses — no further changes allowed.
     */
    public static function terminal(): array
    {
        return [
            self::COMPLETED->value,
            self::CANCELLED->value,
        ];
    }
}
