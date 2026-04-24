<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

// Lumen canonical refactoring set.
//
// Paths are passed by `lens` on the CLI; the empty paths() call
// keeps Rector happy when invoked directly without arguments. Edit
// the set list and rules to evolve the convention.

return RectorConfig::configure()
    ->withPaths([])
    ->withCache(
        cacheDirectory: getcwd() . '/.lens/rector',
    )
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
        SetList::NAMING,
    ])
    ->withImportNames(
        importShortClasses: false,
        removeUnusedImports: true,
    )
    ->withSkip([
        // Rector ships several rules that conflict with Laravel
        // idioms or our php-cs-fixer config. Add exclusions here
        // when a rule produces noise across products.
        InlineConstructorDefaultToPropertyRector::class,
    ])
    ->withRules([
        RemoveUnusedPrivateMethodRector::class,
        RemoveUnusedPrivatePropertyRector::class,
    ]);
