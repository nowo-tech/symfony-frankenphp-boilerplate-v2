<?php

declare(strict_types=1);

namespace App\Identity\Repository;

use App\Identity\Dto\InstanceRoleListRow;
use App\Identity\Entity\InstanceRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InstanceRole>
 */
class InstanceRoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InstanceRole::class);
    }

    /**
     * @return list<InstanceRole>
     */
    public function findAllOrdered(?string $query = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.permissions', 'p')->addSelect('p')
            ->leftJoin('r.users', 'u')->addSelect('u')
            ->leftJoin('r.createdBy', 'cb')->addSelect('cb')
            ->leftJoin('r.updatedBy', 'ub')->addSelect('ub')
            ->orderBy('r.name', 'ASC');

        if (null !== $query && '' !== trim($query)) {
            $qb->andWhere('r.name LIKE :q OR r.code LIKE :q OR r.description LIKE :q')
                ->setParameter('q', '%'.trim($query).'%');
        }

        /** @var list<InstanceRole> $roles */
        $roles = $qb->getQuery()->getResult();

        return $roles;
    }

    /**
     * Roles for the index table with scalar COUNTs (no permissions/users hydration).
     *
     * @return list<InstanceRoleListRow>
     */
    public function findAllOrderedForIndex(?string $query = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.name', 'ASC');

        if (null !== $query && '' !== trim($query)) {
            $qb->andWhere('r.name LIKE :q OR r.code LIKE :q OR r.description LIKE :q')
                ->setParameter('q', '%'.trim($query).'%');
        }

        /** @var list<InstanceRole> $roles */
        $roles = $qb->getQuery()->getResult();
        if ([] === $roles) {
            return [];
        }

        /** @var list<int> $ids */
        $ids = array_map(static fn (InstanceRole $role): int => (int) $role->getId(), $roles);
        $permissionCounts = $this->countRelatedByRoleIds($ids, 'permissions');
        $userCounts = $this->countRelatedByRoleIds($ids, 'users');

        $rows = [];
        foreach ($roles as $role) {
            $id = (int) $role->getId();
            $rows[] = new InstanceRoleListRow(
                $role,
                $permissionCounts[$id] ?? 0,
                $userCounts[$id] ?? 0,
            );
        }

        return $rows;
    }

    /**
     * @param list<int>    $roleIds
     * @param 'permissions'|'users' $association
     *
     * @return array<int, int>
     */
    private function countRelatedByRoleIds(array $roleIds, string $association): array
    {
        $alias = 'users' === $association ? 'u' : 'p';
        /** @var list<array{roleId: int|string, cnt: int|string}> $raw */
        $raw = $this->createQueryBuilder('r')
            ->select('r.id AS roleId, COUNT('.$alias.'.id) AS cnt')
            ->leftJoin('r.'.$association, $alias)
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $roleIds)
            ->groupBy('r.id')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($raw as $row) {
            $counts[(int) $row['roleId']] = (int) $row['cnt'];
        }

        return $counts;
    }

    public function findOneByCode(string $code): ?InstanceRole
    {
        $normalized = strtoupper(trim($code));
        if (!str_starts_with($normalized, 'ROLE_')) {
            $normalized = 'ROLE_'.$normalized;
        }

        return $this->findOneBy(['code' => $normalized]);
    }

    /** Eager-load permissions + users for the role detail page. */
    public function hydrateDetail(InstanceRole $role): void
    {
        $this->createQueryBuilder('r')
            ->leftJoin('r.permissions', 'p')->addSelect('p')
            ->leftJoin('r.users', 'u')->addSelect('u')
            ->leftJoin('r.createdBy', 'cb')->addSelect('cb')
            ->leftJoin('r.updatedBy', 'ub')->addSelect('ub')
            ->andWhere('r = :role')
            ->setParameter('role', $role)
            ->getQuery()
            ->getResult();
    }

    public function countAssignedUsers(InstanceRole $role): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(u.id)')
            ->innerJoin('r.users', 'u')
            ->andWhere('r = :role')
            ->setParameter('role', $role)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Permission keys granted by enabled roles assigned to the user.
     *
     * @return list<string>
     */
    public function findPermissionKeysForUserId(int $userId): array
    {
        /** @var list<string> $keys */
        $keys = $this->createQueryBuilder('r')
            ->select('DISTINCT p.key')
            ->innerJoin('r.users', 'u')
            ->innerJoin('r.permissions', 'p')
            ->andWhere('u.id = :userId')
            ->andWhere('r.enabled = true')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleColumnResult();

        return $keys;
    }
}
