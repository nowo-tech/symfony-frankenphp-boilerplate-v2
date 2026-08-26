<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Form;

use App\Identity\Entity\InstancePermission;
use App\Identity\Entity\InstanceRole;
use App\Identity\Entity\User;
use App\Identity\Form\AccountSecurityType;
use App\Identity\Form\AdminInstancePermissionType;
use App\Identity\Form\AdminInstanceRoleType;
use App\Identity\Form\AdminUserType;
use App\Identity\Form\TypeToConfirmType;
use App\Shared\Rbac\RbacPermissionTranslator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class IdentityFormTypesTest extends KernelTestCase
{
    private FormFactoryInterface $forms;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->forms = self::getContainer()->get(FormFactoryInterface::class);
    }

    public function testAdminUserTypeBuildsExpectedFields(): void
    {
        $form = $this->forms->create(AdminUserType::class, null, ['csrf_protection' => false]);

        self::assertSame('admin_user', $form->getName());
        self::assertTrue($form->has('email'));
        self::assertTrue($form->has('displayName'));
        self::assertTrue($form->has('password'));
        self::assertTrue($form->has('role'));
        self::assertTrue($form->has('enabled'));

        $form->submit([
            'email' => 'admin@example.com',
            'displayName' => 'Admin',
            'password' => 'password123',
            'role' => 'admin',
            'enabled' => '1',
        ]);
        self::assertTrue($form->isSynchronized());
        self::assertSame('admin@example.com', $form->get('email')->getData());
        self::assertSame('admin', $form->get('role')->getData());
    }

    public function testAdminInstanceRoleTypeBuildsAndLocksCode(): void
    {
        $role = new InstanceRole();
        $role->setName('Support');
        $role->setCode('ROLE_SUPPORT');
        $role->setEnabled(true);

        $form = $this->forms->create(AdminInstanceRoleType::class, $role, [
            'csrf_protection' => false,
            'code_locked' => true,
        ]);

        self::assertSame('admin_instance_role', $form->getName());
        self::assertTrue($form->has('name'));
        self::assertTrue($form->has('code'));
        self::assertTrue($form->has('description'));
        self::assertTrue($form->has('enabled'));
        self::assertTrue($form->get('code')->getConfig()->getOption('disabled'));

        $unlocked = $this->forms->create(AdminInstanceRoleType::class, new InstanceRole(), [
            'csrf_protection' => false,
            'code_locked' => false,
        ]);
        self::assertFalse($unlocked->get('code')->getConfig()->getOption('disabled'));
    }

    public function testTypeToConfirmTypeValidatesConfirmationValue(): void
    {
        $form = $this->forms->create(TypeToConfirmType::class, null, [
            'csrf_protection' => false,
            'confirmation_value' => 'DELETE',
        ]);

        self::assertSame('', $form->getConfig()->getType()->getBlockPrefix());
        self::assertTrue($form->has('confirmation'));

        $form->submit(['confirmation' => 'DELETE']);
        self::assertTrue($form->isSynchronized());
        self::assertSame('DELETE', $form->get('confirmation')->getData());
    }

    public function testAccountSecurityTypeBuildsPasswordFields(): void
    {
        $user = new User();
        $user->setEmail('person@example.com');
        $user->setDisplayName('Person');
        $user->setPassword('hashed');

        $form = $this->forms->create(AccountSecurityType::class, $user, ['csrf_protection' => false]);

        self::assertSame('user_preferences', $form->getName());
        self::assertTrue($form->has('currentPassword'));
        self::assertTrue($form->has('plainPassword'));
        self::assertTrue($form->has('plainPassword_confirm'));
        self::assertFalse($form->get('currentPassword')->getConfig()->getMapped());
        self::assertTrue($form->get('plainPassword')->getConfig()->getMapped());
    }

    public function testAdminInstancePermissionTypePopulatesAndSubmitsTranslations(): void
    {
        $permission = new InstancePermission();
        $permission->setKey('admin.users.manage');
        $permission->setName('Manage users');
        $permission->setDescription('Can manage users');
        $permission->setCategory('access');

        $form = $this->forms->create(AdminInstancePermissionType::class, $permission, [
            'csrf_protection' => false,
            'enabled_locales' => ['en', 'es'],
            'default_locale' => 'en',
            'key_locked' => false,
        ]);

        self::assertSame('admin_instance_permission', $form->getName());
        self::assertTrue($form->has('key'));
        self::assertTrue($form->has('category'));
        self::assertTrue($form->has('name_en'));
        self::assertTrue($form->has('description_en'));
        self::assertTrue($form->has('name_es'));
        self::assertSame('Manage users', $form->get('name_en')->getData());
        self::assertSame('Can manage users', $form->get('description_en')->getData());

        $view = $form->createView();
        self::assertSame(['en', 'es'], $view->vars['enabled_locales']);
        self::assertSame('en', $view->vars['default_locale']);

        $form->submit([
            'key' => 'admin.users.manage',
            'category' => 'access',
            'name_en' => 'Users',
            'description_en' => 'Manage all users',
            'name_es' => 'Usuarios',
            'description_es' => 'Gestionar usuarios',
        ]);
        self::assertTrue($form->isSynchronized());
        self::assertSame('Users', $permission->getName());
        self::assertSame('Manage all users', $permission->getDescription());
        self::assertSame('Users', $permission->getNameForLocale('en'));
        self::assertSame('Usuarios', $permission->getNameForLocale('es'));
    }

    public function testAdminInstancePermissionTypeFallsBackToCatalogWhenEmpty(): void
    {
        $permission = new InstancePermission();
        $permission->setKey('admin.roles.manage');
        $permission->setName('');
        $permission->setCategory('access');

        $translator = self::getContainer()->get(TranslatorInterface::class);
        $rbac = self::getContainer()->get(RbacPermissionTranslator::class);
        self::assertInstanceOf(TranslatorInterface::class, $translator);
        self::assertInstanceOf(RbacPermissionTranslator::class, $rbac);

        $form = $this->forms->create(AdminInstancePermissionType::class, $permission, [
            'csrf_protection' => false,
            'enabled_locales' => ['en'],
            'default_locale' => 'en',
            'key_locked' => true,
        ]);

        self::assertTrue($form->get('key')->getConfig()->getOption('disabled'));
        self::assertIsString($form->get('name_en')->getData());
    }

    public function testAdminInstancePermissionTypeIgnoresNonPermissionData(): void
    {
        $form = $this->forms->create(AdminInstancePermissionType::class, null, [
            'csrf_protection' => false,
            'enabled_locales' => ['en'],
            'default_locale' => 'en',
        ]);

        // POST_SET_DATA runs with null and returns early (no translation hydrate).
        self::assertNull($form->get('name_en')->getData());
        self::assertTrue($form->has('description_en'));
    }
}
