<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Commands;

use LumenSistemas\Lens\Drivers\Mode;
use LumenSistemas\Lens\Drivers\PhpCsFixerDriver;
use LumenSistemas\Lens\Drivers\PhpStanDriver;
use LumenSistemas\Lens\Drivers\RectorDriver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'check', description: 'Run all linters in check mode. Exits non-zero on any issue.')]
final class CheckCommand extends OrchestratesDrivers
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->orchestrate(
            drivers: [
                new PhpCsFixerDriver(),
                new RectorDriver(),
                new PhpStanDriver(),
            ],
            defaultMode: Mode::Check,
            input: $input,
            output: $output,
        );
    }
}
