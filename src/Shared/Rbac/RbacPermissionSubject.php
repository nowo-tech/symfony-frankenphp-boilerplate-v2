<?php

declare(strict_types=1);

namespace App\Shared\Rbac;

/**
 * A permission row {@see RbacPermissionTranslator} can label (Identity's InstancePermission). Optional
 * `getNameForLocale(string)` / `getDescriptionForLocale(string)` are preferred when present.
 */
interface RbacPermissionSubject
{
    public function getKey(): string;

    public function getName(): string;

    public function getDescription(): ?string;
}
