<?php

declare(strict_types=1);

namespace App\Shared\Menu;

use Nowo\DashboardMenuBundle\Service\AbstractRoutePrefixMenuCurrentMatcher;

/**
 * Marks preferences sidebar links current for whole account areas (exact path match is too narrow).
 */
final class PreferencesMenuCurrentMatcher extends AbstractRoutePrefixMenuCurrentMatcher
{
    /** @var array<string, list<string>> */
    private const array ROUTE_PREFIXES = [
        'account_profile' => ['account_profile', 'account_groups', 'account_privacy'],
        'account_security' => ['account_security', 'account_security_devices'],
        'account_display' => ['account_display'],
    ];

    /** @return array<string, list<string>> */
    protected function routePrefixes(): array
    {
        return self::ROUTE_PREFIXES;
    }
}
