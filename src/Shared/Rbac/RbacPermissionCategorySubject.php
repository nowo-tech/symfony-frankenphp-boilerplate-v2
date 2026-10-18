<?php

declare(strict_types=1);

namespace App\Shared\Rbac;

/**
 * Anything {@see RbacPermissionCategoryTranslator} can read a permission category from (Identity's InstancePermission).
 */
interface RbacPermissionCategorySubject
{
    public function getCategory(): string;
}
