<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

// Lumen canonical PHP code style.
//
// The Finder here is a safety net only — `lens` always passes
// explicit paths on the CLI based on lens.json, which overrides
// this Finder. Editing the rule list below changes the convention
// for every product on `lumensistemas/lens`.

$finder = Finder::create()
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->exclude(['vendor', 'storage', 'bootstrap/cache', 'node_modules'])
    ->in(getcwd() ?: __DIR__);

return (new Config())
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setLineEnding("\n")
    ->setIndent('    ')
    ->setParallelConfig(ParallelConfigFactory::sequential())
    ->setRules([
        '@PSR12' => true,
        '@PHP8x3Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => 'single_space'],
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'try', 'if', 'for', 'foreach', 'while'],
        ],
        'cast_spaces' => ['space' => 'single'],
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
        ],
        'no_extra_blank_lines' => [
            'tokens' => ['extra', 'throw', 'use'],
        ],
        'class_attributes_separation' => [
            'elements' => ['trait_import' => 'none'],
        ],
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'remove_inheritdoc' => true,
        ],
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'php_unit_method_casing' => ['case' => 'snake_case'],
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_order' => true,
        'phpdoc_separation' => true,
        'phpdoc_trim' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'single_quote' => true,
        'single_trait_insert_per_statement' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters', 'match'],
        ],
        'modifier_keywords' => ['elements' => ['property', 'method', 'const']],
        'void_return' => true,
        'yoda_style' => false,
    ])
    ->setFinder($finder);
