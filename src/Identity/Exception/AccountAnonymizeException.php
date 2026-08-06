<?php

declare(strict_types=1);

namespace App\Identity\Exception;

use RuntimeException;

/**
 * Raised when GDPR anonymize cannot proceed (last admin, already scrubbed).
 */
final class AccountAnonymizeException extends RuntimeException
{
    public const string ALREADY_ANONYMIZED = 'already_anonymized';
    public const string LAST_ADMIN = 'last_admin';

    public function __construct(
        public readonly string $reasonCode,
        string $message = '',
    ) {
        parent::__construct('' !== $message ? $message : $reasonCode);
    }
}
