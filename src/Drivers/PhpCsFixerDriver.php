<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Drivers;

use LumenSistemas\Lens\Application;
use LumenSistemas\Lens\Config\ProjectConfig;
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
            '--config' => Application::packageRoot() . '/config/php-cs-fixer.php',
            '--cache-file' => $runContext->cacheDir() . '/cs-fixer.cache',
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

        // PHP-CS-Fixer reads PHP_CS_FIXER_IGNORE_ENV from real env, not
        // from input — set it on the process before booting.
        $previous = getenv('PHP_CS_FIXER_IGNORE_ENV');
        putenv('PHP_CS_FIXER_IGNORE_ENV=1');

        try {
            $application = new PhpCsFixerApplication();
            $application->setAutoExit(false);

            return $application->run(new ArrayInput($arguments), $output);
        } finally {
            putenv('PHP_CS_FIXER_IGNORE_ENV' . ($previous === false ? '' : '=' . $previous));
        }
    }
}
