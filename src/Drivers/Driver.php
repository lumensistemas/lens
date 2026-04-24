<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Drivers;

use LumenSistemas\Lens\Config\ProjectConfig;
use Symfony\Component\Console\Output\OutputInterface;

interface Driver
{
    public function name(): string;

    public function supportsFix(): bool;

    public function run(
        Mode $mode,
        ProjectConfig $projectConfig,
        RunContext $runContext,
        OutputInterface $output,
    ): int;
}
