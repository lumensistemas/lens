<?php

declare(strict_types=1);

use LumenSistemas\Lens\Config\ProjectConfig;
use LumenSistemas\Lens\Drivers\Mode;
use LumenSistemas\Lens\Drivers\PhpStanDriver;
use LumenSistemas\Lens\Drivers\RunContext;
use LumenSistemas\Lens\Process\Runner;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

beforeEach(function (): void {
    $this->project = sys_get_temp_dir().'/lens-phpstan-'.uniqid();
    mkdir($this->project.'/src', 0o755, true);
    mkdir($this->project.'/vendor', 0o755);

    $this->capture = new class extends Runner
    {
        /** @var list<string> */
        public array $command = [];

        public function run(array $command, string $cwd, OutputInterface $output, array $env = []): int
        {
            $this->command = $command;

            return 0;
        }
    };
});

afterEach(function (): void {
    if (is_dir($this->project)) {
        $rrm = function (string $path) use (&$rrm): void {
            foreach (scandir($path) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $full = $path.'/'.$entry;
                is_dir($full) && !is_link($full) ? $rrm($full) : unlink($full);
            }
            rmdir($path);
        };
        $rrm($this->project);
    }
});

it('passes --autoload-file pointing at the project autoloader when one exists', function (): void {
    file_put_contents($this->project.'/vendor/autoload.php', "<?php // fake autoloader\n");

    $driver = new PhpStanDriver($this->capture);
    $context = new RunContext(projectRoot: $this->project, packageRoot: $this->project);
    $driver->run(Mode::Check, ProjectConfig::defaults($this->project), $context, new BufferedOutput());

    expect($this->capture->command)
        ->toContain('--autoload-file='.$this->project.'/vendor/autoload.php');
});

it('omits --autoload-file when the project has no composer install', function (): void {
    $driver = new PhpStanDriver($this->capture);
    $context = new RunContext(projectRoot: $this->project, packageRoot: $this->project);
    $driver->run(Mode::Check, ProjectConfig::defaults($this->project), $context, new BufferedOutput());

    foreach ($this->capture->command as $arg) {
        expect($arg)->not->toStartWith('--autoload-file=');
    }
});
