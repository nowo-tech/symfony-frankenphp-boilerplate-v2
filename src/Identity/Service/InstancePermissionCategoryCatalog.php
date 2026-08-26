<?php

declare(strict_types=1);

namespace App\Identity\Service;

/**
 * Allowed permission category machine slugs for Instance RBAC (REQ-RBAC-008).
 *
 * Labels resolve via permissions.category.<slug>.name|description — not stored here.
 */
final class InstancePermissionCategoryCatalog
{
    /** @var list<string> */
    public const array SLUGS = [
        'access',
        'overview',
        'instance',
        'navigation',
        'observability',
        'general',
        'custom',
    ];

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return self::SLUGS;
    }

    /**
     * ChoiceType map: translation key → machine slug.
     *
     * @return array<string, string>
     */
    public static function formChoices(): array
    {
        $choices = [];
        foreach (self::SLUGS as $slug) {
            $choices['permissions.category.'.$slug.'.name'] = $slug;
        }

        return $choices;
    }

    public static function isKnown(string $slug): bool
    {
        return \in_array(strtolower(trim($slug)), self::SLUGS, true);
    }
}
