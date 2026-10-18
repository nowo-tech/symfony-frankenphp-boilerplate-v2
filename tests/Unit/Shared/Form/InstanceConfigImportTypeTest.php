<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Form;

use App\Shared\Settings\Form\InstanceConfigImportType;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class InstanceConfigImportTypeTest extends TestCase
{
    public function testExposesImportFieldMetadata(): void
    {
        $type = new ReflectionClass(InstanceConfigImportType::class)->newInstanceWithoutConstructor();

        self::assertSame('instance_config_import', $type->getBlockPrefix());

        $fileField = new ReflectionMethod(InstanceConfigImportType::class, 'fileFieldName');
        $testId = new ReflectionMethod(InstanceConfigImportType::class, 'fileInputTestId');
        $csrf = new ReflectionMethod(InstanceConfigImportType::class, 'csrfTokenId');

        self::assertSame('config', $fileField->invoke($type));
        self::assertSame('instance-config-file', $testId->invoke($type));
        self::assertSame('instance_config_import', $csrf->invoke($type));
    }
}
