<?php

/**
 * The default PHP-CS-Fixer configuration of the mcvs-php-action.
 *
 * Copy this file to the root of a project and adjust it where needed. The path
 * can be overridden by the PHP_CS_FIXER_CONFIG_PATH variable.
 */

declare(strict_types=1);

$directories = array_values(array_filter(
    [__DIR__ . '/src', __DIR__ . '/tests'],
    static fn (string $directory): bool => is_dir($directory),
));

$finder = PhpCsFixer\Finder::create()
    ->in($directories === [] ? [__DIR__] : $directories)
    ->ignoreDotFiles(true)
    ->ignoreVCSIgnored(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PHP8x3Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_line_throw' => false,
        'strict_param' => true,
        'trailing_comma_in_multiline' => true,
    ])
    ->setFinder($finder);
