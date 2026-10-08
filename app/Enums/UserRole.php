<?php

namespace App\Enums;

enum UserRole: string
{
    case CUSTOMER = 'CUSTOMER';
    case AGENT = 'AGENT';
    case PROFESSIONAL = 'PROFESSIONAL';
    case OPERATIONS = 'OPERATIONS';
    case ADMIN = 'ADMIN';
    case SUPER_ADMIN = 'SUPER_ADMIN';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
