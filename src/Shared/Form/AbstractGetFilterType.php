<?php

declare(strict_types=1);

namespace App\Shared\Form;

use Nowo\FormKitBundle\Form\AbstractGetFilterType as FormKitAbstractGetFilterType;

/**
 * Nowo Shell GET / list filters — FormKit profile {@code filter} via the kit base type.
 *
 * Generic CSRF-only / GET filter factories live in {@see Nowo\FormKitBundle\Form}.
 */
abstract class AbstractGetFilterType extends FormKitAbstractGetFilterType
{
}
