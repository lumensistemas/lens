<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Drivers;

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
        if (! is_dir($this->cacheDir())) {
            mkdir($this->cacheDir(), 0o755, true);
        }
    }
}
