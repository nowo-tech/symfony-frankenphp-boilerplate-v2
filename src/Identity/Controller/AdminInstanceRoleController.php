<?php

declare(strict_types=1);

namespace App\Identity\Controller;

use App\Identity\Entity\InstancePermission;
use App\Identity\Entity\InstanceRole;
use App\Identity\Entity\User;
use App\Identity\Form\AdminInstanceRoleType;
use App\Identity\Form\AdminRolePermissionsType;
use App\Identity\Form\AdminRoleUserAddType;
use App\Identity\Form\RoleFilterType;
use App\Identity\Repository\InstancePermissionRepository;
use App\Identity\Repository\InstanceRoleRepository;
use App\Identity\Repository\UserRepository;
use App\Identity\Service\InstancePermissionMatrixBuilder;
use App\Identity\Service\UserActionRecorder;
use App\Identity\UserActionType;
use App\Shared\Controller\RequiresValidFormTrait;
use Doctrine\ORM\EntityManagerInterface;
use Nowo\FormKitBundle\Form\CsrfOnlyFormFactory;
use Nowo\FormKitBundle\Form\GetFilterFormFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Admin CRUD for instance RBAC roles (tabbed overview, permissions, and user assignment).
 */
#[IsGranted('ROLE_ADMIN')]
final class AdminInstanceRoleController extends AbstractController
{
    use RequiresValidFormTrait;

    public function __construct(
        private readonly InstanceRoleRepository $roleRepository,
        private readonly InstancePermissionRepository $permissionRepository,
        private readonly InstancePermissionMatrixBuilder $permissionMatrixBuilder,
        private readonly UserRepository $userRepository,
        private readonly UserActionRecorder $actionRecorder,
        private readonly EntityManagerInterface $entityManager,
        private readonly CsrfOnlyFormFactory $csrfOnlyFormFactory,
        private readonly GetFilterFormFactory $filterFormFactory,
    ) {
    }

    #[Route('/admin/roles', name: 'admin_roles', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filterForm = $this->filterFormFactory->create(RoleFilterType::class, [], [
            'action' => $this->generateUrl('admin_roles'),
        ]);
        $filterForm->handleRequest($request);
        $query = $filterForm->isSubmitted() && $filterForm->isValid()
            ? (string) ($filterForm->get('q')->getData() ?? '')
            : $request->query->getString('q');
        $roles = $this->roleRepository->findAllOrderedForIndex('' !== $query ? $query : null);

        return $this->render('admin/roles/index.html.twig', [
            'roles' => $roles,
            'q' => $query,
            'filter_form' => $filterForm->createView(),
        ]);
    }

    #[Route('/admin/roles/new', name: 'admin_roles_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $role = new InstanceRole();
        $role->setEnabled(true);
        $form = $this->createForm(AdminInstanceRoleType::class, $role, [
            'csrf_token_id' => 'admin_instance_role_new',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->roleRepository->findOneByCode($role->getCode()) instanceof InstanceRole) {
                $this->addFlash('error', 'flash.roles.code_taken');

                return $this->redirectToRoute('admin_roles_new');
            }

            /** @var User $actor */
            $actor = $this->getUser();
            $this->entityManager->persist($role);
            $this->actionRecorder->record(
                UserActionType::InstanceRoleCreated,
                $actor,
                null,
                ['role' => $role->getName(), 'code' => $role->getCode(), 'role_uuid' => $role->getUuid()],
            );
            $this->entityManager->flush();
            $this->addFlash('success', 'flash.roles.created');

            return $this->redirectToRoute('admin_roles_show', ['id' => $role->getUuid()]);
        }

        return $this->render('admin/roles/form.html.twig', [
            'form' => $form,
            'role' => $role,
            'is_edit' => false,
        ]);
    }

    #[Route('/admin/roles/{id}', name: 'admin_roles_show', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    public function show(
        #[MapEntity(mapping: ['id' => 'uuid'])]
        InstanceRole $role,
    ): Response {
        $this->roleRepository->hydrateDetail($role);

        return $this->render('admin/roles/show.html.twig', [
            'role' => $role,
            'deleteForm' => $this->roleDeleteForm($role),
        ]);
    }

    #[Route('/admin/roles/{id}/edit', name: 'admin_roles_edit', requirements: ['id' => Requirement::UUID], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(mapping: ['id' => 'uuid'])]
        InstanceRole $role,
    ): Response {
        $originalCode = $role->getCode();
        $form = $this->createForm(AdminInstanceRoleType::class, $role, [
            'csrf_token_id' => 'admin_instance_role_edit_'.$role->getId(),
            'code_locked' => $role->isSystem(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$role->isSystem()) {
                $existing = $this->roleRepository->findOneByCode($role->getCode());
                if ($existing instanceof InstanceRole && $existing->getId() !== $role->getId()) {
                    $role->setCode($originalCode);
                    $this->addFlash('error', 'flash.roles.code_taken');

                    return $this->redirectToRoute('admin_roles_edit', ['id' => $role->getUuid()]);
                }
            } else {
                $role->setCode($originalCode);
            }

            /** @var User $actor */
            $actor = $this->getUser();
            $this->actionRecorder->record(
                UserActionType::InstanceRoleUpdated,
                $actor,
                null,
                ['role' => $role->getName(), 'code' => $role->getCode(), 'role_uuid' => $role->getUuid()],
            );
            $this->entityManager->flush();
            $this->addFlash('success', 'flash.roles.updated');

            return $this->redirectToRoute('admin_roles_show', ['id' => $role->getUuid()]);
        }

        return $this->render('admin/roles/form.html.twig', [
            'form' => $form,
            'role' => $role,
            'is_edit' => true,
        ]);
    }

    #[Route('/admin/roles/{id}/delete', name: 'admin_roles_delete', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    public function delete(
        Request $request,
        #[MapEntity(mapping: ['id' => 'uuid'])]
        InstanceRole $role,
    ): RedirectResponse {
        if ($role->isSystem()) {
            $this->addFlash('error', 'flash.roles.system_locked');

            return $this->redirectToRoute('admin_roles_show', ['id' => $role->getUuid()]);
        }

        $this->roleRepository->hydrateDetail($role);
        if ($this->roleRepository->countAssignedUsers($role) > 0) {
            $this->addFlash('error', 'flash.roles.in_use');

            return $this->redirectToRoute('admin_roles_show', ['id' => $role->getUuid()]);
        }

        $form = $this->csrfOnlyFormFactory->createNamed(
            $this->generateUrl('admin_roles_delete', ['id' => $role->getUuid()]),
            'admin_instance_role_delete_'.$role->getId(),
        );
        $form->handleRequest($request);
        $this->requireValidCsrfForm($form);

        /** @var User $actor */
        $actor = $this->getUser();
        $this->actionRecorder->record(
            UserActionType::InstanceRoleDeleted,
            $actor,
            null,
            ['role' => $role->getName(), 'code' => $role->getCode(), 'role_uuid' => $role->getUuid()],
        );
        $this->entityManager->remove($role);
        $this->entityManager->flush();
        $this->addFlash('success', 'flash.roles.deleted');

        return $this->redirectToRoute('admin_roles');
    }

    #[Route('/admin/roles/{id}/permissions', name: 'admin_roles_permissions', requirements: ['id' => Requirement::UUID], methods: ['GET', 'POST'])]
    public function permissions(
        Request $request,
        #[MapEntity(mapping: ['id' => 'uuid'])]
        InstanceRole $role,
    ): Response {
        $this->roleRepository->hydrateDetail($role);

        $permissionsByCategory = $this->permissionMatrixBuilder->permissionsByCategory();
        $permissionIds = [];
        foreach ($permissionsByCategory as $permissions) {
            foreach ($permissions as $permission) {
                $permissionId = $permission->getId();
                if (null !== $permissionId) {
                    $permissionIds[] = $permissionId;
                }
            }
        }

        $form = $this->createForm(AdminRolePermissionsType::class, $this->permissionsFormData($role, $permissionIds), [
            'action' => $this->generateUrl('admin_roles_permissions', ['id' => $role->getUuid()]),
            'method' => 'POST',
            'csrf_token_id' => 'admin_instance_role_permissions_'.$role->getId(),
            'permission_ids' => $permissionIds,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, bool> $data */
            $data = $form->getData();
            $selectedIds = [];
            foreach ($permissionIds as $permissionId) {
                if (true === ($data['permission_'.$permissionId] ?? false)) {
                    $selectedIds[] = $permissionId;
                }
            }

            $role->clearPermissions();
            if ([] !== $selectedIds) {
                /** @var list<InstancePermission> $selectedPermissions */
                $selectedPermissions = $this->permissionRepository->findBy(['id' => $selectedIds]);
                foreach ($selectedPermissions as $permission) {
                    $role->addPermission($permission);
                }
            }

            /** @var User $actor */
            $actor = $this->getUser();
            $this->actionRecorder->record(
                UserActionType::InstanceRolePermissionsUpdated,
                $actor,
                null,
                [
                    'role' => $role->getName(),
                    'code' => $role->getCode(),
                    'role_uuid' => $role->getUuid(),
                    'permission_count' => \count($selectedIds),
                ],
            );
            $this->entityManager->flush();
            $this->addFlash('success', 'flash.roles.permissions_updated');

            return $this->redirectToRoute('admin_roles_permissions', ['id' => $role->getUuid()]);
        }

        return $this->render('admin/roles/permissions.html.twig', [
            'role' => $role,
            'permissions_by_category' => $permissionsByCategory,
            'deleteForm' => $this->roleDeleteForm($role),
            'permissionsForm' => $form->createView(),
        ]);
    }

    #[Route('/admin/roles/{id}/users', name: 'admin_roles_users', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    public function users(
        #[MapEntity(mapping: ['id' => 'uuid'])]
        InstanceRole $role,
    ): Response {
        $this->roleRepository->hydrateDetail($role);

        $removeUserForms = [];
        foreach ($role->getUsers() as $user) {
            $userId = $user->getId();
            if (null === $userId) {
                continue;
            }
            $removeUserForms[$userId] = $this->csrfOnlyFormFactory->createNamed(
                $this->generateUrl('admin_roles_users_remove', [
                    'id' => $role->getUuid(),
                    'userId' => $user->getUuid(),
                ]),
                'admin_instance_role_user_remove_'.$role->getId().'_'.$userId,
            )->createView();
        }

        return $this->render('admin/roles/users.html.twig', [
            'role' => $role,
            'deleteForm' => $this->roleDeleteForm($role),
            'addUserForm' => $this->createForm(AdminRoleUserAddType::class, [
                'email' => '',
            ], [
                'action' => $this->generateUrl('admin_roles_users_add', ['id' => $role->getUuid()]),
                'method' => 'POST',
                'csrf_token_id' => 'admin_instance_role_user_add_'.$role->getId(),
            ])->createView(),
            'removeUserForms' => $removeUserForms,
        ]);
    }

    #[Route('/admin/roles/{id}/users/add', name: 'admin_roles_users_add', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    public function addUser(
        Request $request,
        #[MapEntity(mapping: ['id' => 'uuid'])]
        InstanceRole $role,
    ): RedirectResponse {
        $form = $this->createForm(AdminRoleUserAddType::class, null, [
            'csrf_token_id' => 'admin_instance_role_user_add_'.$role->getId(),
        ]);
        $form->handleRequest($request);
        $this->requireValidForm($form);

        $email = strtolower(trim((string) $form->get('email')->getData()));
        $user = $this->userRepository->findOneByEmail($email);
        if (!$user instanceof User) {
            $this->addFlash('error', 'flash.roles.user_not_found');

            return $this->redirectToRoute('admin_roles_users', ['id' => $role->getUuid()]);
        }

        if ($user->hasInstanceRole($role)) {
            $this->addFlash('error', 'flash.roles.user_already');

            return $this->redirectToRoute('admin_roles_users', ['id' => $role->getUuid()]);
        }

        $user->addInstanceRole($role);

        /** @var User $actor */
        $actor = $this->getUser();
        $this->actionRecorder->record(
            UserActionType::InstanceRoleUserAdded,
            $actor,
            $user,
            ['role' => $role->getName(), 'code' => $role->getCode(), 'role_uuid' => $role->getUuid()],
        );
        $this->entityManager->flush();
        $this->addFlash('success', 'flash.roles.user_added');

        return $this->redirectToRoute('admin_roles_users', ['id' => $role->getUuid()]);
    }

    #[Route('/admin/roles/{id}/users/{userId}/remove', name: 'admin_roles_users_remove', requirements: ['id' => Requirement::UUID, 'userId' => Requirement::UUID], methods: ['POST'])]
    public function removeUser(
        Request $request,
        #[MapEntity(mapping: ['id' => 'uuid'])]
        InstanceRole $role,
        #[MapEntity(mapping: ['userId' => 'uuid'])]
        User $user,
    ): RedirectResponse {
        $form = $this->csrfOnlyFormFactory->createNamed(
            $this->generateUrl('admin_roles_users_remove', [
                'id' => $role->getUuid(),
                'userId' => $user->getUuid(),
            ]),
            'admin_instance_role_user_remove_'.$role->getId().'_'.$user->getId(),
        );
        $form->handleRequest($request);
        $this->requireValidCsrfForm($form);

        $user->removeInstanceRole($role);

        /** @var User $actor */
        $actor = $this->getUser();
        $this->actionRecorder->record(
            UserActionType::InstanceRoleUserRemoved,
            $actor,
            $user,
            ['role' => $role->getName(), 'code' => $role->getCode(), 'role_uuid' => $role->getUuid()],
        );
        $this->entityManager->flush();
        $this->addFlash('success', 'flash.roles.user_removed');

        return $this->redirectToRoute('admin_roles_users', ['id' => $role->getUuid()]);
    }

    private function roleDeleteForm(InstanceRole $role): FormView
    {
        return $this->csrfOnlyFormFactory->createNamed(
            $this->generateUrl('admin_roles_delete', ['id' => $role->getUuid()]),
            'admin_instance_role_delete_'.$role->getId(),
        )->createView();
    }

    /**
     * @param list<int> $permissionIds
     *
     * @return array<string, bool>
     */
    private function permissionsFormData(InstanceRole $role, array $permissionIds): array
    {
        $data = [];
        foreach ($permissionIds as $permissionId) {
            $data['permission_'.$permissionId] = false;
        }
        foreach ($role->getPermissions() as $permission) {
            $permissionId = $permission->getId();
            if (null !== $permissionId) {
                $data['permission_'.$permissionId] = true;
            }
        }

        return $data;
    }
}
