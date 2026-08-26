<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Security;

use App\Identity\Entity\InstancePermission;
use App\Identity\Entity\User;
use App\Identity\Repository\InstancePermissionRepository;
use App\Identity\Repository\InstanceRoleRepository;
use App\Identity\Security\InstancePermissionVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class InstancePermissionVoterTest extends TestCase
{
    private InstancePermissionRepository&MockObject $permissions;
    private InstanceRoleRepository&MockObject $roles;
    private InstancePermissionVoter $voter;

    protected function setUp(): void
    {
        $this->permissions = $this->createMock(InstancePermissionRepository::class);
        $this->roles = $this->createMock(InstanceRoleRepository::class);
        $this->voter = new InstancePermissionVoter($this->permissions, $this->roles);
    }

    public function testIgnoresRoleAttributes(): void
    {
        $user = new User();
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, null, ['ROLE_ADMIN']));
    }

    public function testAdminGrantedAnyKnownPermission(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->permissions->expects(self::never())->method('findOneByKey');

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, null, ['admin.users.manage']),
        );
    }

    public function testAssignedPermissionGranted(): void
    {
        $user = new User();
        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setValue($user, 7);

        $permission = new InstancePermission();
        $permission->setKey('admin.mailer.manage');

        $this->permissions->method('findOneByKey')->with('admin.mailer.manage')->willReturn($permission);
        $this->roles->method('findPermissionKeysForUserId')->with(7)->willReturn(['admin.mailer.manage']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, null, ['admin.mailer.manage']),
        );
    }

    public function testUnknownPermissionDenied(): void
    {
        $user = new User();
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->permissions->method('findOneByKey')->willReturn(null);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, null, ['admin.unknown.thing']),
        );
    }
}
