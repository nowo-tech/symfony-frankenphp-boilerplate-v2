<?php

declare(strict_types=1);

namespace App\Shared\Rbac;

/**
 * A role row {@see RbacRoleTranslator} can label (Identity's InstanceRole).
 */
interface RbacRoleSubject
{
    public function getCode(): string;

    public function getName(): string;

    public function getDescription(): ?string;
}
