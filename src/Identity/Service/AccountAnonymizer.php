<?php

declare(strict_types=1);

namespace App\Identity\Service;

use App\Identity\Entity\PasswordHistory;
use App\Identity\Entity\User;
use App\Identity\Exception\AccountAnonymizeException;
use App\Identity\Repository\UserRepository;
use App\Identity\UserActionType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Nowo\AuthKitBundle\Entity\SocialLoginAccount;
use Nowo\AuthKitBundle\Repository\SocialLoginAccountRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Scrubs personal identifiers and disables login (GDPR soft-delete).
 */
final readonly class AccountAnonymizer
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private SocialLoginAccountRepository $socialLoginAccountRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private UserActionRecorder $actionRecorder,
    ) {
    }

    /**
     * @throws AccountAnonymizeException
     */
    public function anonymize(User $subject, User $actor): void
    {
        if ($subject->isAnonymized()) {
            throw new AccountAnonymizeException(AccountAnonymizeException::ALREADY_ANONYMIZED);
        }

        if ($this->isLastAdmin($subject)) {
            throw new AccountAnonymizeException(AccountAnonymizeException::LAST_ADMIN);
        }

        $previousEmail = $subject->getEmail();
        $uuid = $subject->getUuid();

        $subject->setEmail('anonymized-'.$uuid.'@invalid.local');
        $subject->setDisplayName('Anonymized user');
        $subject->setSlackUserId(null);
        $subject->setPhone(null);
        $subject->setPhoneVerifiedAt(null);
        $subject->setEnabled(false);
        $subject->setAnonymizedAt(new DateTimeImmutable());
        $subject->setPasswordResetToken(null);
        $subject->setPasswordResetExpiresAt(null);
        $subject->getUiPreferences()->resetForAnonymize();
        $subject->setRoles([]);

        $random = bin2hex(random_bytes(32));
        $subject->setPassword($this->passwordHasher->hashPassword($subject, $random));
        $subject->clearPlainPassword();

        foreach ($subject->getPasswordHistory()->toArray() as $history) {
            if ($history instanceof PasswordHistory) {
                $subject->removePasswordHistory($history);
                $this->entityManager->remove($history);
            }
        }

        $userId = $subject->getId();
        if (null !== $userId) {
            /** @var list<SocialLoginAccount> $socialAccounts */
            $socialAccounts = $this->socialLoginAccountRepository->findBy([
                'userClass' => User::class,
                'userId' => (string) $userId,
            ]);
            foreach ($socialAccounts as $account) {
                $this->entityManager->remove($account);
            }
        }

        $this->actionRecorder->record(
            UserActionType::UserAnonymized,
            $actor,
            $subject,
            [
                'previous_email_domain' => $this->emailDomain($previousEmail),
                'uuid' => $uuid,
            ],
        );

        $this->entityManager->flush();
    }

    public function isLastAdmin(User $user): bool
    {
        if (!\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return false;
        }

        return $this->userRepository->countAdmins(excludeAnonymized: true) <= 1;
    }

    private function emailDomain(string $email): string
    {
        $at = strrpos($email, '@');
        if (false === $at) {
            return 'unknown';
        }

        return substr($email, $at + 1);
    }
}
