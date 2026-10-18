<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Service;

use App\Identity\Entity\InstancePermission;
use App\Identity\Repository\InstancePermissionRepository;
use App\Identity\Service\InstancePermissionMatrixBuilder;
use PHPUnit\Framework\TestCase;

final class InstancePermissionMatrixBuilderTest extends TestCase
{
    public function testGroupsPermissionsByCategory(): void
    {
        $admin = new InstancePermission()->setKey('admin.users')->setCategory('administration');
        $audit = new InstancePermission()->setKey('audit.read')->setCategory('audit');

        $repo = $this->createStub(InstancePermissionRepository::class);
        $repo->method('findAllOrdered')->willReturn([$admin, $audit]);

        $builder = new InstancePermissionMatrixBuilder($repo);

        self::assertSame(
            ['administration' => [$admin], 'audit' => [$audit]],
            $builder->permissionsByCategory(),
        );
    }
}
