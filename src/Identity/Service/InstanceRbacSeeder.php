<?php

declare(strict_types=1);

namespace App\Identity\Service;

use App\Identity\Entity\InstancePermission;
use App\Identity\Repository\InstancePermissionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Idempotent upsert of the built-in instance permission catalog.
 */
final class InstanceRbacSeeder
{
    public function __construct(
        private readonly InstancePermissionRepository $permissionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return bool true when at least one permission was created or updated
     */
    public function seedIfEmpty(): bool
    {
        $changed = false;

        foreach (InstancePermissionCatalog::definitions() as $definition) {
            $permission = $this->permissionRepository->findOneByKey($definition['key']);
            if (!$permission instanceof InstancePermission) {
                $permission = new InstancePermission();
                $permission->setKey($definition['key']);
                $this->entityManager->persist($permission);
                $changed = true;
            }

            if (
                $permission->getName() !== $definition['name']
                || $permission->getDescription() !== $definition['description']
                || $permission->getCategory() !== $definition['category']
                || !$permission->isSystem()
            ) {
                $permission->setName($definition['name']);
                $permission->setDescription($definition['description']);
                $permission->setCategory($definition['category']);
                $permission->setSystem(true);
                $changed = true;
            }
        }

        if ($changed) {
            $this->entityManager->flush();
        }

        return $changed;
    }
}
