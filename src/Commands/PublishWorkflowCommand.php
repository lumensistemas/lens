<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Commands;

use LumenSistemas\Lens\Application;
use LumenSistemas\Lens\Process\Quietly;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'publish:workflow', description: 'Drop a GitHub Actions workflow that runs lens (and tests, for packages).')]
final class PublishWorkflowCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('package', null, InputOption::VALUE_NONE, 'Deploy the package test matrix workflow (lint + tests across PHP / stability / OS).');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite an existing workflow.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('package')) {
            $output->writeln('<error>lens publish:workflow: pass --package (the only supported workflow mode today).</error>');

            return Command::FAILURE;
        }

        $projectRoot = getcwd() ?: '.';
        $source = Application::packageRoot().'/stubs/github-actions-test-packages.yml';
        $targetDir = $projectRoot.'/.github/workflows';
        $relative = '.github/workflows/tests.yml';
        $target = $targetDir.'/tests.yml';

        if (!is_dir($targetDir)) {
            $created = Quietly::call(fn (): bool => mkdir($targetDir, 0o755, true));

            if (!$created && !is_dir($targetDir)) {
                throw new RuntimeException("lens publish:workflow: failed to create {$targetDir}");
            }
        }

        if (file_exists($target) && !$input->getOption('force')) {
            $output->writeln("<comment>skip</comment> {$relative} (already exists)");

            return Command::SUCCESS;
        }

        if (!Quietly::call(fn (): bool => copy($source, $target))) {
            throw new RuntimeException("lens publish:workflow: failed to write {$target}");
        }
        $output->writeln("<info>wrote</info> {$relative}");

        return Command::SUCCESS;
    }
}
