<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Service;

use App\Identity\Entity\PasswordHistory;
use App\Identity\Entity\User;
use App\Identity\Exception\AccountAnonymizeException;
use App\Identity\Repository\UserRepository;
use App\Identity\Service\AccountAnonymizer;
use App\Identity\Service\UserActionRecorder;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Nowo\AuthKitBundle\Entity\SocialLoginAccount;
use Nowo\AuthKitBundle\Repository\SocialLoginAccountRepository;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccountAnonymizerTest extends TestCase
{
    public function testAnonymizeScrubsIdentifiersAndRemovesRelatedRows(): void
    {
        $subject = new User();
        $subject->setEmail('keep@example.com');
        $subject->setDisplayName('Keep Me');
        $subject->setSlackUserId('U123');
        $subject->setPhone('+100');
        $subject->setPhoneVerifiedAt(new DateTimeImmutable());
        $subject->setRoles(['ROLE_ADMIN']);
        $subject->setPreferredTheme('dark');
        $this->setUserId($subject, 42);

        $history = new PasswordHistory();
        $history->setUser($subject);
        $history->setPassword('old-hash');
        $subject->addPasswordHistory($history);

        $actor = new User();
        $actor->setEmail('actor@example.com');

        $social = $this->createMock(SocialLoginAccount::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(2))->method('remove')->with(self::logicalOr($history, $social));
        $em->expects(self::atLeastOnce())->method('persist');
        $em->expects(self::once())->method('flush');

        $users = $this->createMock(UserRepository::class);
        $users->expects(self::atLeastOnce())->method('countAdmins')->with(true)->willReturn(2);

        $socialRepo = $this->createMock(SocialLoginAccountRepository::class);
        $socialRepo->method('findBy')->willReturn([$social]);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed-random');

        $recorder = new UserActionRecorder($em, new RequestStack());

        new AccountAnonymizer($em, $users, $socialRepo, $hasher, $recorder)->anonymize($subject, $actor);

        self::assertTrue($subject->isAnonymized());
        self::assertFalse($subject->isEnabled());
        self::assertNull($subject->getSlackUserId());
        self::assertNull($subject->getPhone());
        self::assertSame('Anonymized user', $subject->getDisplayName());
        self::assertStringStartsWith('anonymized-', $subject->getEmail());
        self::assertSame('hashed-random', $subject->getPassword());
        self::assertSame([], $subject->getPasswordHistory()->toArray());
        self::assertNull($subject->getPreferredThemeRaw());
    }

    public function testAnonymizeRejectsAlreadyAnonymized(): void
    {
        $subject = new User();
        $subject->setEmail('x@example.com');
        $subject->setAnonymizedAt(new DateTimeImmutable());

        $this->expectException(AccountAnonymizeException::class);
        $this->expectExceptionMessage(AccountAnonymizeException::ALREADY_ANONYMIZED);

        $this->anonymizer()->anonymize($subject, new User());
    }

    public function testAnonymizeRejectsLastAdmin(): void
    {
        $subject = new User();
        $subject->setEmail('admin@example.com');
        $subject->setRoles(['ROLE_ADMIN']);

        $users = $this->createMock(UserRepository::class);
        $users->method('countAdmins')->willReturn(1);

        $this->expectException(AccountAnonymizeException::class);
        $this->expectExceptionMessage(AccountAnonymizeException::LAST_ADMIN);

        new AccountAnonymizer(
            $this->createMock(EntityManagerInterface::class),
            $users,
            $this->createMock(SocialLoginAccountRepository::class),
            $this->createMock(UserPasswordHasherInterface::class),
            new UserActionRecorder($this->createMock(EntityManagerInterface::class), new RequestStack()),
        )->anonymize($subject, new User());
    }

    public function testIsLastAdminFalseForNonAdmin(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);

        self::assertFalse($this->anonymizer()->isLastAdmin($user));
    }

    public function testEmailDomainFallsBackToUnknown(): void
    {
        $subject = new User();
        $subject->setEmail('not-an-email');
        $this->setUserId($subject, 7);

        $users = $this->createMock(UserRepository::class);
        $users->method('countAdmins')->willReturn(0);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('x');

        $socialRepo = $this->createMock(SocialLoginAccountRepository::class);
        $socialRepo->method('findBy')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::atLeastOnce())->method('persist');
        $em->expects(self::once())->method('flush');

        $recorder = new UserActionRecorder($em, new RequestStack());

        new AccountAnonymizer($em, $users, $socialRepo, $hasher, $recorder)
            ->anonymize($subject, new User());

        self::assertTrue($subject->isAnonymized());
    }

    private function anonymizer(): AccountAnonymizer
    {
        return new AccountAnonymizer(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserRepository::class),
            $this->createMock(SocialLoginAccountRepository::class),
            $this->createMock(UserPasswordHasherInterface::class),
            new UserActionRecorder($this->createMock(EntityManagerInterface::class), new RequestStack()),
        );
    }

    private function setUserId(User $user, int $id): void
    {
        $prop = new ReflectionProperty(User::class, 'id');
        $prop->setValue($user, $id);
    }
}
