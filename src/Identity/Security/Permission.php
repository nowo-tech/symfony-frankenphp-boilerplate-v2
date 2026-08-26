<?php

declare(strict_types=1);

namespace App\Identity\Security;

use App\Identity\Service\InstancePermissionCatalog;

/**
 * Typed instance permission keys (Security attributes for {@see InstancePermissionVoter}).
 *
 * Catalog metadata (name / description / category) lives in {@see InstancePermissionCatalog}.
 *
 * @see docs/product/ROLES.md
 */
final class Permission
{
    /** Lowercase dotted keys with ≥2 segments (e.g. admin.users.manage). */
    public const string KEY_PATTERN = '/^[a-z][a-z0-9_.]*(\.[a-z][a-z0-9_]*)+$/';

    // Access
    public const string ADMIN_ROLES_MANAGE = 'admin.roles.manage';

    public const string ADMIN_USERS_MANAGE = 'admin.users.manage';

    public const string ADMIN_GROUPS_MANAGE = 'admin.groups.manage';

    // Instance
    public const string ADMIN_APPEARANCE_MANAGE = 'admin.appearance.manage';

    public const string ADMIN_MAILER_MANAGE = 'admin.mailer.manage';

    public const string ADMIN_SOCIAL_LOGIN_MANAGE = 'admin.social_login.manage';

    public const string ADMIN_INSTANCE_CONFIG_MANAGE = 'admin.instance_config.manage';

    public const string ADMIN_SETUP_MANAGE = 'admin.setup.manage';

    // Navigation
    public const string ADMIN_MENUS_MANAGE = 'admin.menus.manage';

    public const string ADMIN_BREADCRUMBS_MANAGE = 'admin.breadcrumbs.manage';

    public const string ADMIN_COOKIE_CONSENT_MANAGE = 'admin.cookie_consent.manage';

    public const string ADMIN_ROUTING_MANAGE = 'admin.routing.manage';

    // Observability
    public const string ADMIN_HTTP_LOG_VIEW = 'admin.http_log.view';

    /**
     * Whether the attribute is a capability key (not Symfony ROLE_* / IS_*).
     */
    public static function isDottedCapability(string $attribute): bool
    {
        if (!str_contains($attribute, '.')) {
            return false;
        }

        if (str_starts_with($attribute, 'ROLE_') || str_starts_with($attribute, 'IS_')) {
            return false;
        }

        return true;
    }

    /**
     * Built-in catalog keys (excludes custom DB-only permissions).
     *
     * @return list<string>
     */
    public static function allValues(): array
    {
        return array_values(array_map(
            static fn (array $definition): string => $definition['key'],
            InstancePermissionCatalog::definitions(),
        ));
    }

    public static function isKnown(string $permission): bool
    {
        return \in_array(strtolower(trim($permission)), self::allValues(), true);
    }

    /**
     * @codeCoverageIgnore empty private ctor blocks instantiation
     */
    private function __construct()
    {
    }
}
