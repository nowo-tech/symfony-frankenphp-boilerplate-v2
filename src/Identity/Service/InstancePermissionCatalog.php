<?php

declare(strict_types=1);

namespace App\Identity\Service;

use App\Identity\Security\Permission;

/**
 * Built-in instance permission keys aligned with Administration surfaces.
 *
 * Keys are Security attributes for {@see \App\Identity\Security\InstancePermissionVoter}.
 */
final class InstancePermissionCatalog
{
    /**
     * @return list<array{key: string, name: string, description: string, category: string}>
     */
    public static function definitions(): array
    {
        return [
            [
                'key' => Permission::ADMIN_ROLES_MANAGE,
                'name' => 'Manage roles & permissions',
                'description' => 'Create instance roles, edit permission matrices, and assign roles to users.',
                'category' => 'access',
            ],
            [
                'key' => Permission::ADMIN_USERS_MANAGE,
                'name' => 'Manage users',
                'description' => 'List accounts, change ROLE_ADMIN, enable/disable, export, and anonymize.',
                'category' => 'access',
            ],
            [
                'key' => Permission::ADMIN_GROUPS_MANAGE,
                'name' => 'Manage groups',
                'description' => 'Create user groups and manage group membership.',
                'category' => 'access',
            ],
            [
                'key' => Permission::ADMIN_APPEARANCE_MANAGE,
                'name' => 'Manage appearance',
                'description' => 'Brand name, themes, and page surface colours.',
                'category' => 'instance',
            ],
            [
                'key' => Permission::ADMIN_MAILER_MANAGE,
                'name' => 'Manage mailer',
                'description' => 'Encrypted Mailer DSN and From address.',
                'category' => 'instance',
            ],
            [
                'key' => Permission::ADMIN_SOCIAL_LOGIN_MANAGE,
                'name' => 'Manage social login',
                'description' => 'OAuth provider credentials stored in the database.',
                'category' => 'instance',
            ],
            [
                'key' => Permission::ADMIN_INSTANCE_CONFIG_MANAGE,
                'name' => 'Manage instance config',
                'description' => 'Export or import non-secret appearance and instance flags.',
                'category' => 'instance',
            ],
            [
                'key' => Permission::ADMIN_SETUP_MANAGE,
                'name' => 'Manage setup wizard',
                'description' => 'Re-run platform / demo / sample seed steps.',
                'category' => 'instance',
            ],
            [
                'key' => Permission::ADMIN_MENUS_MANAGE,
                'name' => 'Manage menus',
                'description' => 'Dashboard menu kit admin CRUD.',
                'category' => 'navigation',
            ],
            [
                'key' => Permission::ADMIN_BREADCRUMBS_MANAGE,
                'name' => 'Manage breadcrumbs',
                'description' => 'Breadcrumb kit collections and items.',
                'category' => 'navigation',
            ],
            [
                'key' => Permission::ADMIN_COOKIE_CONSENT_MANAGE,
                'name' => 'Manage cookie consent',
                'description' => 'Consent modal copy, categories, and cookie inventory.',
                'category' => 'navigation',
            ],
            [
                'key' => Permission::ADMIN_ROUTING_MANAGE,
                'name' => 'Manage locale routes',
                'description' => 'Routing kit dual-locale public paths.',
                'category' => 'navigation',
            ],
            [
                'key' => Permission::ADMIN_HTTP_LOG_VIEW,
                'name' => 'View HTTP log',
                'description' => 'Browse captured HTTP requests in the HTTP log admin.',
                'category' => 'observability',
            ],
        ];
    }
}
