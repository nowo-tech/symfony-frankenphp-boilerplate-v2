<?php

declare(strict_types=1);

namespace App\Identity\Tour;

/**
 * Optional first-run tour page ids (platform shell).
 * Product apps may extend this enum with their own page contexts.
 */
enum ProductTourPage: string
{
    case Dashboard = 'dashboard';
    case Admin = 'admin';

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return self::cases();
    }
}
