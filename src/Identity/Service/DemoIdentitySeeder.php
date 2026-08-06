<?php

declare(strict_types=1);

namespace App\Identity\Service;

use App\Identity\Entity\User;
use App\Identity\Repository\UserRepository;
use DateTime;
use LogicException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Creates the local demo admin user (CLI and SiteBackup setup).
 */
final readonly class DemoIdentitySeeder
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @return array{user_created: bool, user: User}
     *
     * @throws LogicException when $createDemoUser is false and no ROLE_ADMIN exists
     */
    public function seed(
        string $email = 'admin@nowo-shell.local',
        string $password = 'admin123',
        bool $createDemoUser = true,
    ): array {
        $userCreated = false;
        $user = $this->userRepository->findOneByEmail($email);

        if (!$user instanceof User && $createDemoUser) {
            $user = new User();
            $user->setEmail($email);
            $user->setDisplayName('Demo Admin');
            $user->setRoles(['ROLE_ADMIN']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $user->setPasswordChangedAt(new DateTime());
            $this->userRepository->save($user);
            $userCreated = true;
        }

        if (!$user instanceof User) {
            $user = $this->findFirstInstanceAdmin() ?? $this->userRepository->findOneBy([]);
        }

        if (!$user instanceof User) {
            throw new LogicException('Cannot resolve a demo admin. Register an admin first, or run app:seed-demo without --skip-demo-user.');
        }

        return [
            'user_created' => $userCreated,
            'user' => $user,
        ];
    }

    private function findFirstInstanceAdmin(): ?User
    {
        foreach ($this->userRepository->findAll() as $candidate) {
            if (\in_array('ROLE_ADMIN', $candidate->getRoles(), true)) {
                return $candidate;
            }
        }

        return null;
    }
}
