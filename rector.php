<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

/*
 * Rector — semantic upgrades only (PHP 8.5, dead code, types, Symfony attributes).
 *
 * Formatting, imports, native `\fn()`, union order (`string|null`), and PER-CS /
 * Symfony style belong to PHP-CS-Fixer. Do not enable SetList::CODING_STYLE,
 * withImportNames(), or CS-Fixer @PHP*Migration sets (Rector owns language level).
 *
 * Always apply CS Fixer after Rector (`make rector-fix` / composer rector-fix).
 */

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/var',
        __DIR__.'/vendor',
    ])
    // Match composer.json "php": ">=8.5" / FrankenPHP image.
    ->withPhpSets(php85: true)
    ->withSets([
        LevelSetList::UP_TO_PHP_85,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
        SymfonySetList::SYMFONY_81,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
    ])
    ->withAttributesSets(symfony: true, doctrine: true)
    ->withImportNames(removeUnusedImports: true)
;
