<?php

declare(strict_types=1);

use LumenSistemas\Lens\Application;
use LumenSistemas\Lens\Process\VendorPath;

it('resolves vendor() to the on-disk vendor when not running from a phar', function (): void {
    expect(VendorPath::vendor())->toBe(Application::packageRoot() . '/vendor');
});

it('resolves packageRoot() to the on-disk repo root when not running from a phar', function (): void {
    expect(VendorPath::packageRoot())->toBe(Application::packageRoot());
});
