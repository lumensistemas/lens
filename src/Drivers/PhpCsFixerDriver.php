<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Drivers;

use LumenSistemas\Lens\Config\ProjectConfig;
use LumenSistemas\Lens\Process\VendorPath;
use PhpCsFixer\Console\Application as PhpCsFixerApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class PhpCsFixerDriver implements Driver
{
    public function name(): string
    {
        return 'php-cs-fixer';
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

        $paths = $runContext->targets($projectConfig);

        if ($paths === null) {
            return 0;
        }

        $arguments = [
            'command' => 'fix',
            'path' => $paths,
            '--config' => VendorPath::packageRoot().'/config/php-cs-fixer.php',
            '--cache-file' => $runContext->cacheDir().'/cs-fixer.cache',
            '--using-cache' => 'yes',
            '--show-progress' => 'none',
        ];

        if ($runContext->ci) {
            $arguments['--format'] = 'checkstyle';
        }

        if ($mode === Mode::Check) {
            $arguments['--dry-run'] = true;
            $arguments['--diff'] = true;
        }

        $bin = VendorPath::vendor().'/friendsofphp/php-cs-fixer/php-cs-fixer';

        return $this->withSpoofedEntrypoint($bin, function () use ($arguments, $output): int {
            $application = new PhpCsFixerApplication();
            $application->setAutoExit(false);

            return $application->run(new ArrayInput($arguments), $output);
        });
    }

    /**
     * Embedded in lens, $_SERVER['SCRIPT_FILENAME'] points at the
     * lens binary, so PHP-CS-Fixer's parallel runner would re-spawn
     * lens as a worker instead of itself. Spoof the script vars
     * (and PHP_CS_FIXER_IGNORE_ENV) for the duration of the call,
     * restoring on any exit path so the rest of lens sees its
     * original environment.
     *
     * @template T
     *
     * @param callable(): T $fn
     *
     * @return T
     */
    private function withSpoofedEntrypoint(string $bin, callable $fn): mixed
    {
        $savedScriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? null;
        $savedScriptName = $_SERVER['SCRIPT_NAME'] ?? null;
        $savedArgv = is_array($_SERVER['argv'] ?? null) ? $_SERVER['argv'] : null;
        $savedEnv = getenv('PHP_CS_FIXER_IGNORE_ENV');

        $_SERVER['SCRIPT_FILENAME'] = $bin;
        $_SERVER['SCRIPT_NAME'] = $bin;
        $_SERVER['argv'] = [$bin, ...array_slice($savedArgv ?? [], 1)];
        putenv('PHP_CS_FIXER_IGNORE_ENV=1');

        try {
            return $fn();
        } finally {
            $this->restoreServerVar('SCRIPT_FILENAME', $savedScriptFilename);
            $this->restoreServerVar('SCRIPT_NAME', $savedScriptName);
            $this->restoreServerVar('argv', $savedArgv);
            putenv('PHP_CS_FIXER_IGNORE_ENV'.($savedEnv === false ? '' : '='.$savedEnv));
        }
    }

    private function restoreServerVar(string $key, mixed $previous): void
    {
        if ($previous === null) {
            unset($_SERVER[$key]);

            return;
        }
        $_SERVER[$key] = $previous;
    }
}
