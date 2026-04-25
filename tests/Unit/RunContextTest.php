<?php

declare(strict_types=1);

use LumenSistemas\Lens\Drivers\RunContext;

beforeEach(function (): void {
    $this->root = sys_get_temp_dir() . '/lens-runctx-' . uniqid();
    mkdir($this->root, 0o755, true);
});

afterEach(function (): void {
    if (is_dir($this->root . '/.lens')) {
        @chmod($this->root . '/.lens', 0o755);
        @rmdir($this->root . '/.lens');
    }
    @chmod($this->root, 0o755);
    @rmdir($this->root);
});

it('creates the .lens cache dir on demand', function (): void {
    $context = new RunContext($this->root, packageRoot: $this->root);

    $context->ensureCacheDir();

    expect(is_dir($this->root . '/.lens'))->toBeTrue();
});

it('is a no-op when the cache dir already exists', function (): void {
    mkdir($this->root . '/.lens', 0o755);
    $context = new RunContext($this->root, packageRoot: $this->root);

    $context->ensureCacheDir();
    $context->ensureCacheDir();

    expect(is_dir($this->root . '/.lens'))->toBeTrue();
});

it('throws a clear error when the project root is not writable', function (): void {
    if (posix_geteuid() === 0) {
        $this->markTestSkipped('root user bypasses POSIX write permissions');
    }
    chmod($this->root, 0o555);

    $context = new RunContext($this->root, packageRoot: $this->root);

    expect(fn () => $context->ensureCacheDir())
        ->toThrow(RuntimeException::class, 'failed to create cache dir');
});
