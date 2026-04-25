<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Commands;

use LumenSistemas\Lens\Drivers\Driver;
use LumenSistemas\Lens\Drivers\Mode;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Single configurable command used for every lens command. Shape:
 * a name, a driver list, a default Mode, and (for per-tool
 * commands) an opt-in --fix flag that flips the mode at runtime.
 *
 * Replaces five near-identical command classes that differed only
 * in those four values.
 */
final class ToolCommand extends OrchestratesDrivers
{
    /**
     * @param list<Driver> $drivers
     */
    public function __construct(
        string $name,
        string $description,
        private readonly array $drivers,
        private readonly Mode $defaultMode,
        private readonly bool $supportsFixToggle = false,
    ) {
        parent::__construct($name);
        $this->setDescription($description);
    }

    #[Override]
    protected function configure(): void
    {
        parent::configure();

        if ($this->supportsFixToggle) {
            $this->addOption('fix', null, InputOption::VALUE_NONE, 'Apply fixes instead of checking.');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mode = $this->supportsFixToggle && $input->getOption('fix')
            ? Mode::Fix
            : $this->defaultMode;

        return $this->orchestrate($this->drivers, $mode, $input, $output);
    }
}
