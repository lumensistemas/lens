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

        $paths = $runContext->dirtyFiles ?? $projectConfig->paths();

        if ($runContext->dirtyFiles !== null && $paths === []) {
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

        // PHP-CS-Fixer reads PHP_CS_FIXER_IGNORE_ENV from real env,
        // not from input — set it on the process before booting.
        $previous = getenv('PHP_CS_FIXER_IGNORE_ENV');
        putenv('PHP_CS_FIXER_IGNORE_ENV=1');

        // Parallel mode in PHP-CS-Fixer spawns workers that re-invoke
        // $_SERVER['SCRIPT_FILENAME']. When embedded inside lens this
        // would point at the lens binary (or PHAR), which is not a
        // cs-fixer worker entry point. Point it at cs-fixer's actual
        // bin so spawned workers boot cs-fixer rather than lens.
        $bin = VendorPath::vendor().'/friendsofphp/php-cs-fixer/php-cs-fixer';
        $savedScriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? null;
        $savedScriptName = $_SERVER['SCRIPT_NAME'] ?? null;
        $savedArgv = is_array($_SERVER['argv'] ?? null) ? $_SERVER['argv'] : null;
        $_SERVER['SCRIPT_FILENAME'] = $bin;
        $_SERVER['SCRIPT_NAME'] = $bin;
        $_SERVER['argv'] = [$bin, ...array_slice($savedArgv ?? [], 1)];

        try {
            $application = new PhpCsFixerApplication();
            $application->setAutoExit(false);

            return $application->run(new ArrayInput($arguments), $output);
        } finally {
            if ($savedScriptFilename === null) {
                unset($_SERVER['SCRIPT_FILENAME']);
            } else {
                $_SERVER['SCRIPT_FILENAME'] = $savedScriptFilename;
            }

            if ($savedScriptName === null) {
                unset($_SERVER['SCRIPT_NAME']);
            } else {
                $_SERVER['SCRIPT_NAME'] = $savedScriptName;
            }

            if ($savedArgv === null) {
                unset($_SERVER['argv']);
            } else {
                $_SERVER['argv'] = $savedArgv;
            }

            putenv('PHP_CS_FIXER_IGNORE_ENV'.($previous === false ? '' : '='.$previous));
        }
    }
}
