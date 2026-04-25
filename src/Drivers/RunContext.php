<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Drivers;

use LumenSistemas\Lens\Config\ProjectConfig;
use LumenSistemas\Lens\Process\Quietly;
use RuntimeException;

final readonly class RunContext
{
    /**
     * @param null|list<string> $dirtyFiles null when --dirty is not in use
     */
    public function __construct(
        public string $projectRoot,
        public string $packageRoot,
        public bool $ci = false,
        public ?array $dirtyFiles = null,
    ) {}

    public function cacheDir(): string
    {
        return $this->projectRoot.'/.lens';
    }

    /**
     * Files/dirs each driver should analyse: the dirty-files list
     * when --dirty is set, otherwise the project config paths.
     * Returns null when --dirty resolved to nothing — drivers should
     * short-circuit and skip the tool entirely in that case.
     *
     * @return null|list<string>
     */
    public function targets(ProjectConfig $config): ?array
    {
        if ($this->dirtyFiles === []) {
            return null;
        }

        return $this->dirtyFiles ?? $config->paths;
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

        if (!$created && !is_dir($dir)) {
            throw new RuntimeException("lens: failed to create cache dir {$dir}");
        }
    }
}
