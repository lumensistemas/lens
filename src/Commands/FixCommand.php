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

#[AsCommand(name: 'fix', description: 'Apply automatic fixes, then verify with PHPStan.')]
final class FixCommand extends OrchestratesDrivers
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->orchestrate(
            drivers: [
                new PhpCsFixerDriver(),
                new RectorDriver(),
                new PhpStanDriver(),
            ],
            defaultMode: Mode::Fix,
            input: $input,
            output: $output,
        );
    }
}
