<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Commands;

use LumenSistemas\Lens\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'init', description: 'Write lens.json and an empty PHPStan baseline into the project.')]
final class InitCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite files that already exist.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectRoot = getcwd() ?: '.';
        $stubsRoot = Application::packageRoot() . '/stubs';
        $force = (bool) $input->getOption('force');

        $files = [
            'lens.json' => $stubsRoot . '/lens.json',
            'phpstan-baseline.neon' => $stubsRoot . '/phpstan-baseline.neon',
        ];

        foreach ($files as $name => $source) {
            $target = $projectRoot . '/' . $name;

            if (file_exists($target) && ! $force) {
                $output->writeln("<comment>skip</comment> {$name} (already exists)");

                continue;
            }
            copy($source, $target);
            $output->writeln("<info>wrote</info> {$name}");
        }

        return Command::SUCCESS;
    }
}
