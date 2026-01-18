<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['public', 'assets', 'config', 'logs', 'vendor', 'node_modules'])
    ->name('*.php')
    ->notPath('bin/')
    ->notPath('docker/')
    ->notPath('public/')
    ->notPath('Migrations/')
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'concat_space' => ['spacing' => 'none'],
        'phpdoc_trim' => true,
        'phpdoc_align' => ['align' => 'left'],
        'no_unused_imports' => true,
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
        'no_superfluous_phpdoc_tags' => true,
        'no_blank_lines_after_phpdoc' => true,
        'declare_strict_types' => true,
        'yoda_style' => false,
    ])
    ->setFinder($finder)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect());
;
