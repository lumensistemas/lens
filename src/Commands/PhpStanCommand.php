<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Commands;

use LumenSistemas\Lens\Drivers\Mode;
use LumenSistemas\Lens\Drivers\PhpStanDriver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'phpstan', description: 'Run only PHPStan.')]
final class PhpStanCommand extends OrchestratesDrivers
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->orchestrate(
            drivers: [new PhpStanDriver()],
            defaultMode: Mode::Check,
            input: $input,
            output: $output,
        );
    }
}
