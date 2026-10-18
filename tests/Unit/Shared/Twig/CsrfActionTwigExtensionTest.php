<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Twig;

use App\Shared\Twig\CsrfActionTwigExtension;
use Nowo\FormKitBundle\Form\CsrfOnlyFormFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

final class CsrfActionTwigExtensionTest extends TestCase
{
    public function testCsrfActionTwigExtensionBuildsForms(): void
    {
        $view = new FormView();
        $form = $this->createStub(FormInterface::class);
        $form->method('createView')->willReturn($view);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects(self::once())
            ->method('createNamed')
            ->willReturn($form);
        $formFactory->expects(self::exactly(3))
            ->method('create')
            ->willReturn($form);

        $ext = new CsrfActionTwigExtension(new CsrfOnlyFormFactory($formFactory), $formFactory);
        self::assertSame($view, $ext->csrfActionForm('/action', 'token-id'));
        self::assertSame($view, $ext->csrfActionForm('/action', 'token-id', fields: ['enabled' => '1']));
        self::assertSame($view, $ext->searchQueryForm('/search', 'q', ['placeholder' => 'Search']));
        self::assertSame($view, $ext->flatHiddenFields(['_section' => 'config']));
    }
}
