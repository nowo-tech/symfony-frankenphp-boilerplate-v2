<?php

declare(strict_types=1);

namespace App\Identity\Dto;

use App\Identity\Entity\InstanceRole;

/** List row for /admin/roles — avoids N+1 cartesian hydration (N1-002). */
final readonly class InstanceRoleListRow
{
    public function __construct(
        public InstanceRole $role,
        public int $permissionCount,
        public int $userCount,
    ) {
    }
}
