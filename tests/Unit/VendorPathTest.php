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

describe('assertSafeForRemoval', function (): void {
    beforeEach(function (): void {
        $this->savedXdg = getenv('XDG_CACHE_HOME');
        $this->cacheHome = sys_get_temp_dir() . '/lens-vp-' . uniqid();
        $this->elsewhere = sys_get_temp_dir() . '/lens-vp-elsewhere-' . uniqid();
        mkdir($this->cacheHome . '/lens', 0o755, true);
        mkdir($this->elsewhere, 0o755, true);
        putenv('XDG_CACHE_HOME=' . $this->cacheHome);
    });

    afterEach(function (): void {
        $this->savedXdg === false
            ? putenv('XDG_CACHE_HOME')
            : putenv('XDG_CACHE_HOME=' . $this->savedXdg);
        rrm($this->cacheHome);
        rrm($this->elsewhere);
    });

    it('accepts a real directory under the lens cache root', function (): void {
        $cache = $this->cacheHome . '/lens/' . Application::VERSION;
        mkdir($cache, 0o755);

        VendorPath::assertSafeForRemoval($cache);
    })->throwsNoExceptions();

    it('refuses a cache path that is itself a symlink', function (): void {
        $cache = $this->cacheHome . '/lens/' . Application::VERSION;
        symlink($this->elsewhere, $cache);

        VendorPath::assertSafeForRemoval($cache);
    })->throws(RuntimeException::class, 'is a symlink');

    it('refuses a cache path whose canonical resolution lies outside the lens cache root', function (): void {
        $cache = $this->cacheHome . '/lens/' . Application::VERSION;
        $hijack = $this->cacheHome . '/lens';
        rmdir($hijack);
        symlink($this->elsewhere, $hijack);
        mkdir($cache, 0o755);

        VendorPath::assertSafeForRemoval($cache);
    })->throws(RuntimeException::class, 'resolves outside the lens cache root');

    it('refuses a cache path that does not exist (cannot be canonicalized)', function (): void {
        $cache = $this->cacheHome . '/lens/' . Application::VERSION;

        VendorPath::assertSafeForRemoval($cache);
    })->throws(RuntimeException::class, 'cannot resolve canonical path');
});

function rrm(string $path): void
{
    if (is_link($path)) {
        unlink($path);

        return;
    }

    if (! is_dir($path)) {
        if (file_exists($path)) {
            unlink($path);
        }

        return;
    }

    foreach (scandir($path) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        rrm($path . '/' . $entry);
    }
    rmdir($path);
}
