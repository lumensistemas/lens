<?php

declare(strict_types=1);

// Pest configuration for the lens test suite.
//
// Tests are plain Pest closures bound to the default
// PHPUnit\Framework\TestCase. Lens has no Laravel application
// context, no DB, and no HTTP layer, so no custom base class is
// needed.

uses()->in(__DIR__);
