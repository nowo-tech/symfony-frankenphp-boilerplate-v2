<?php

declare(strict_types=1);

/*
 * PHP-CS-Fixer — PER-CS 3.0 (PSR-12 successor) + official Symfony Coding Standards.
 *
 * This tool owns formatting, imports, native `\fn()`, and union/nullable style.
 * Rector owns PHP 8.5 language upgrades, dead code, type inference, and attributes.
 * Do not enable @PHP*Migration / @AutoPHPMigration here (would fight Rector withPhpSets).
 *
 * Run CS Fixer after Rector (`make rector-fix`).
 */

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'var',
        'vendor',
        '.docker',
        '.cursor',
        '.specify',
    ])
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'native_function_invocation' => [
            'include' => ['@compiler_optimized'],
            'scope' => 'namespaced',
            'strict' => true,
        ],
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/.php-cs-fixer.cache')
;
