<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Commands;

use LumenSistemas\Lens\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'publish:workflow', description: 'Drop a GitHub Actions workflow that runs `lens check`.')]
final class PublishWorkflowCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite an existing workflow.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectRoot = getcwd() ?: '.';
        $source = Application::packageRoot() . '/stubs/github-actions.yml';
        $targetDir = $projectRoot . '/.github/workflows';
        $target = $targetDir . '/lens.yml';

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0o755, true);
        }

        if (file_exists($target) && ! $input->getOption('force')) {
            $output->writeln('<comment>skip</comment> .github/workflows/lens.yml (already exists)');

            return Command::SUCCESS;
        }

        copy($source, $target);
        $output->writeln('<info>wrote</info> .github/workflows/lens.yml');

        return Command::SUCCESS;
    }
}
