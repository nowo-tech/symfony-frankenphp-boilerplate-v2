<?php

declare(strict_types=1);

namespace App\Identity\Form;

use App\Shared\Form\AbstractGetFilterType;
use Override;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * GET filter for the admin group directory (/admin/groups?q=…).
 *
 * Profile: {@code filter} (via {@see AbstractGetFilterType} + {@code #[FormKitConfig('filter')]}).
 * CSRF: off (idempotent GET).
 */
final class GroupFilterType extends AbstractGetFilterType
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
        return 'group_filter';
    }
}
