<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Drivers;

use LumenSistemas\Lens\Config\ProjectConfig;
use LumenSistemas\Lens\Process\Runner;
use LumenSistemas\Lens\Process\VendorPath;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class RectorDriver implements Driver
{
    public function __construct(private Runner $runner = new Runner()) {}

    public function name(): string
    {
        return 'rector';
    }

    public function supportsFix(): bool
    {
        return true;
    }

    public function run(
        Mode $mode,
        ProjectConfig $projectConfig,
        RunContext $runContext,
        OutputInterface $output,
    ): int {
        $runContext->ensureCacheDir();

        $bin = VendorPath::vendor().'/rector/rector/bin/rector';
        $configPath = VendorPath::packageRoot().'/config/rector.php';

        $command = [
            PHP_BINARY,
            $bin,
            'process',
            '--config='.$configPath,
            '--no-progress-bar',
        ];

        if ($mode === Mode::Check) {
            $command[] = '--dry-run';
        }

        if ($runContext->ci) {
            $command[] = '--output-format=github';
        }

        $paths = $runContext->targets($projectConfig);

        if ($paths === null) {
            return 0;
        }

        foreach ($paths as $path) {
            $command[] = $path;
        }

        return $this->runner->run(
            $command,
            $runContext->projectRoot,
            $output,
            ['RECTOR_CACHE_DIRECTORY' => $runContext->cacheDir().'/rector'],
        );
    }
}
