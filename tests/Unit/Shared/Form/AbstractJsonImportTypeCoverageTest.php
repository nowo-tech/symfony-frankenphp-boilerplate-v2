<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Form;

use App\Shared\Settings\Form\InstanceConfigImportType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

final class AbstractJsonImportTypeCoverageTest extends KernelTestCase
{
    public function testInstanceConfigImportTypeBuildsFileFieldViaAbstractBase(): void
    {
        self::bootKernel();
        $forms = self::getContainer()->get(FormFactoryInterface::class);

        $form = $forms->create(InstanceConfigImportType::class, null, ['csrf_protection' => false]);

        self::assertSame('instance_config_import', $form->getName());
        self::assertTrue($form->has('config'));
        $config = $form->get('config')->getConfig();
        self::assertFalse($config->getMapped());
        self::assertSame('application/json,.json', $config->getOption('attr')['accept'] ?? null);
        self::assertSame('instance-config-file', $config->getOption('attr')['data-testid'] ?? null);
        self::assertSame('instance_config_import', $form->getConfig()->getOption('csrf_token_id'));
    }
}
