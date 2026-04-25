<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Commands;

use LumenSistemas\Lens\Application;
use LumenSistemas\Lens\Config\ProjectConfig;
use LumenSistemas\Lens\Drivers\Driver;
use LumenSistemas\Lens\Drivers\DriverSelection;
use LumenSistemas\Lens\Drivers\Mode;
use LumenSistemas\Lens\Drivers\RunContext;
use LumenSistemas\Lens\Output\Reporter;
use LumenSistemas\Lens\Process\DirtyFiles;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class OrchestratesDrivers extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('dirty', null, InputOption::VALUE_NONE, 'Only files changed vs. the merge-base with main.')
            ->addOption('ci', null, InputOption::VALUE_NONE, 'Use CI-friendly output (GitHub annotations).')
            ->addOption('using', null, InputOption::VALUE_REQUIRED, 'Comma-separated subset of drivers to run.')
            ->addOption('base', null, InputOption::VALUE_REQUIRED, 'Git ref to compare against in --dirty mode.', 'origin/main');
    }

    /**
     * @param list<Driver> $drivers
     * @param array<string, Mode> $modeOverrides Per-driver mode (e.g., phpstan stays in check during fix).
     */
    protected function orchestrate(
        array $drivers,
        Mode $defaultMode,
        InputInterface $input,
        OutputInterface $output,
        array $modeOverrides = [],
    ): int {
        $projectRoot = getcwd() ?: '.';
        $projectConfig = ProjectConfig::load($projectRoot);
        $ci = (bool) $input->getOption('ci');

        $dirtyFiles = null;

        if ((bool) $input->getOption('dirty')) {
            $base = $input->getOption('base');

            if (! is_string($base)) {
                throw new RuntimeException('lens: --base must be a string');
            }
            $dirtyFiles = DirtyFiles::relativeTo($projectRoot, $base);
        }

        $runContext = new RunContext(
            projectRoot: $projectRoot,
            packageRoot: Application::packageRoot(),
            ci: $ci,
            dirtyFiles: $dirtyFiles,
        );

        $using = $input->getOption('using') ?? '';

        if (! is_string($using)) {
            throw new RuntimeException('lens: --using must be a string');
        }
        $selected = DriverSelection::fromUsing($drivers, $using);
        $reporter = new Reporter($output, $ci);

        foreach ($selected as $driver) {
            $mode = $modeOverrides[$driver->name()] ?? $defaultMode;

            if ($mode === Mode::Fix && ! $driver->supportsFix()) {
                $mode = Mode::Check;
            }

            $reporter->startTool($driver->name());
            $exit = $driver->run($mode, $projectConfig, $runContext, $output);
            $reporter->endTool($driver->name(), $exit);
        }

        return $reporter->summarize();
    }
}
