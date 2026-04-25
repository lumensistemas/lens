<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Drivers;

use LumenSistemas\Lens\Process\Quietly;
use RuntimeException;

final readonly class RunContext
{
    /**
     * @param list<string>|null $dirtyFiles Null when --dirty is not in use.
     */
    public function __construct(
        public string $projectRoot,
        public string $packageRoot,
        public bool $ci = false,
        public ?array $dirtyFiles = null,
    ) {
    }

    public function cacheDir(): string
    {
        return $this->projectRoot . '/.lens';
    }

    public function ensureCacheDir(): void
    {
        $dir = $this->cacheDir();

        if (is_dir($dir)) {
            return;
        }

        // The is_dir() guard handles the race where a concurrent
        // process created the directory between our check and mkdir
        // returning false.
        $created = Quietly::call(fn (): bool => mkdir($dir, 0o755, true));

        if (! $created && ! is_dir($dir)) {
            throw new RuntimeException("lens: failed to create cache dir {$dir}");
        }
    }
}
