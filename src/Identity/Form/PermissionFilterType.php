<?php

declare(strict_types=1);

namespace App\Identity\Form;

use App\Shared\Form\AbstractGetFilterType;
use Override;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * GET filter for the admin permissions catalog (/admin/permissions?q=…).
 *
 * Profile: {@code filter} (via {@see AbstractGetFilterType}).
 * CSRF: off (idempotent GET).
 */
final class PermissionFilterType extends AbstractGetFilterType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->withBuilder($builder, function (): void {
            $this->addNamedField('q', 'search', [
                'required' => false,
                'help' => false,
                'attr' => ['class' => 'input'],
            ]);
        });
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'permission_filter';
    }
}
