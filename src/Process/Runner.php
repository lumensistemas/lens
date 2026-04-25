<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Process;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Runner
{
    /**
     * @param list<string> $command
     * @param array<string, string> $env
     */
    public function run(
        array $command,
        string $cwd,
        OutputInterface $output,
        array $env = [],
    ): int {
        $process = new Process($command, $cwd, $env, null, null);

        return $process->run(static function (string $type, string $buffer) use ($output): void {
            $output->write($buffer);
        });
    }
}
