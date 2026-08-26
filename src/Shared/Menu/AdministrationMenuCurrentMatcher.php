<?php

declare(strict_types=1);

namespace App\Shared\Menu;

use Nowo\DashboardMenuBundle\Service\AbstractRoutePrefixMenuCurrentMatcher;

/**
 * Marks administration sidebar links current for kit/admin areas (exact path match is too narrow).
 */
final class AdministrationMenuCurrentMatcher extends AbstractRoutePrefixMenuCurrentMatcher
{
    /** @var array<string, list<string>> */
    private const array ROUTE_PREFIXES = [
        'admin_appearance' => ['admin_appearance'],
        'admin_cookie_consent' => ['admin_cookie_consent', 'nowo_cookie_consent_'],
        'nowo_dashboard_menu_dashboard_index' => ['nowo_dashboard_menu_'],
        'nowo_breadcrumb_kit_dashboard_collections_index' => ['nowo_breadcrumb_kit_'],
        'nowo_routing_kit_panel' => ['nowo_routing_kit_'],
        'nowo_http_log_admin_index' => ['nowo_http_log_'],
        'nowo_site_backup_setup' => ['nowo_site_backup_'],
        'nowo_maintenance_mode_panel_index' => ['nowo_maintenance_mode_'],
    ];

    /** @return array<string, list<string>> */
    protected function routePrefixes(): array
    {
        return self::ROUTE_PREFIXES;
    }
}
