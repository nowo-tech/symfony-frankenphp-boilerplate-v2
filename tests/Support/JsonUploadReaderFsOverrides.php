<?php

declare(strict_types=1);

namespace App\Shared\Http;

/**
 * Test doubles for root-owned filesystems where chmod(000) stays readable.
 * Only activates for path suffixes used by JsonUploadReaderTest.
 */
function is_readable(string $filename): bool
{
    if (str_ends_with($filename, '.force-unreadable')) {
        return false;
    }

    return \is_readable($filename);
}

function file_get_contents(string $filename, bool $use_include_path = false, $context = null, int $offset = 0, ?int $length = null): string|false
{
    if (str_ends_with($filename, '.force-read-fail')) {
        return false;
    }

    if (null !== $length) {
        return \file_get_contents($filename, $use_include_path, $context, $offset, $length);
    }

    return \file_get_contents($filename, $use_include_path, $context, $offset);
}
