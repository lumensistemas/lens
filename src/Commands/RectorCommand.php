<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Commands;

use LumenSistemas\Lens\Drivers\Mode;
use LumenSistemas\Lens\Drivers\RectorDriver;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'rector', description: 'Run only Rector.')]
final class RectorCommand extends OrchestratesDrivers
{
    #[Override]
    protected function configure(): void
    {
        parent::configure();
        $this->addOption('fix', null, InputOption::VALUE_NONE, 'Apply refactors instead of checking.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->orchestrate(
            drivers: [new RectorDriver()],
            defaultMode: $input->getOption('fix') ? Mode::Fix : Mode::Check,
            input: $input,
            output: $output,
        );
    }
}
