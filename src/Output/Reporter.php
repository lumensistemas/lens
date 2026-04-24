<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Output;

use Symfony\Component\Console\Output\OutputInterface;

final class Reporter
{
    /** @var array<string, int> */
    private array $results = [];

    public function __construct(
        private readonly OutputInterface $output,
        private readonly bool $ci = false,
    ) {
    }

    public function startTool(string $name): void
    {
        if ($this->ci) {
            $this->output->writeln('::group::' . $name);

            return;
        }

        $this->output->writeln('');
        $this->output->writeln("<info>── {$name} ──</info>");
    }

    public function endTool(string $name, int $exitCode): void
    {
        $this->results[$name] = $exitCode;

        if ($this->ci) {
            $this->output->writeln('::endgroup::');
        }
    }

    public function summarize(): int
    {
        $worst = 0;
        $lines = [];

        foreach ($this->results as $tool => $code) {
            $status = $code === 0 ? '<info>ok</info>' : "<error>fail ({$code})</error>";
            $lines[] = sprintf('  %-14s %s', $tool, $status);
            $worst = max($worst, $code);
        }

        $this->output->writeln('');
        $this->output->writeln('<comment>lens summary</comment>');

        foreach ($lines as $line) {
            $this->output->writeln($line);
        }
        $this->output->writeln('');

        return $worst;
    }
}
