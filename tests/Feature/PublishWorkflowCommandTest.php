<?php

declare(strict_types=1);

use LumenSistemas\Lens\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    $this->cwd = getcwd();
    $this->project = sys_get_temp_dir() . '/lens-publish-' . uniqid();
    mkdir($this->project, 0o755, true);
    chdir($this->project);
});

afterEach(function (): void {
    chdir($this->cwd);

    if (is_dir($this->project)) {
        $rrmdir = function (string $path) use (&$rrmdir): void {
            foreach (scandir($path) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $full = $path . '/' . $entry;
                is_dir($full) && ! is_link($full) ? $rrmdir($full) : unlink($full);
            }
            rmdir($path);
        };
        $rrmdir($this->project);
    }
});

it('writes .github/workflows/lens.yml under the project root', function (): void {
    $application = new Application();
    $application->setAutoExit(false);
    $output = new BufferedOutput();

    $exit = $application->run(new ArrayInput(['command' => 'publish:workflow']), $output);

    expect($exit)->toBe(0);
    expect(file_exists($this->project . '/.github/workflows/lens.yml'))->toBeTrue();
    expect(file_get_contents($this->project . '/.github/workflows/lens.yml'))
        ->toContain('lens check --ci');
});

it('skips an existing workflow unless --force is passed', function (): void {
    mkdir($this->project . '/.github/workflows', 0o755, true);
    file_put_contents($this->project . '/.github/workflows/lens.yml', 'sentinel');

    $application = new Application();
    $application->setAutoExit(false);
    $output = new BufferedOutput();

    $application->run(new ArrayInput(['command' => 'publish:workflow']), $output);

    expect(file_get_contents($this->project . '/.github/workflows/lens.yml'))
        ->toBe('sentinel');

    $application->run(
        new ArrayInput(['command' => 'publish:workflow', '--force' => true]),
        $output,
    );

    expect(file_get_contents($this->project . '/.github/workflows/lens.yml'))
        ->not->toBe('sentinel');
});
