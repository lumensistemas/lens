<?php

declare(strict_types=1);

use LumenSistemas\Lens\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    $this->cwd = getcwd();
    $this->project = sys_get_temp_dir() . '/lens-init-' . uniqid();
    mkdir($this->project, 0o755, true);
    chdir($this->project);
});

afterEach(function (): void {
    chdir($this->cwd);

    if (is_dir($this->project)) {
        foreach (scandir($this->project) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                unlink($this->project . '/' . $entry);
            }
        }
        rmdir($this->project);
    }
});

it('copies lens.json and the phpstan baseline into the project root', function (): void {
    $application = new Application();
    $application->setAutoExit(false);
    $output = new BufferedOutput();

    $exit = $application->run(new ArrayInput(['command' => 'init']), $output);

    expect($exit)->toBe(0);
    expect(file_exists($this->project . '/lens.json'))->toBeTrue();
    expect(file_exists($this->project . '/phpstan-baseline.neon'))->toBeTrue();
    expect($output->fetch())->toContain('wrote');
});

it('skips files that already exist unless --force is passed', function (): void {
    file_put_contents($this->project . '/lens.json', '{"paths":["custom"]}');

    $application = new Application();
    $application->setAutoExit(false);
    $output = new BufferedOutput();

    $application->run(new ArrayInput(['command' => 'init']), $output);

    expect(file_get_contents($this->project . '/lens.json'))
        ->toBe('{"paths":["custom"]}');
    expect($output->fetch())->toContain('skip');
});

it('overwrites existing files when --force is passed', function (): void {
    file_put_contents($this->project . '/lens.json', '{"paths":["custom"]}');

    $application = new Application();
    $application->setAutoExit(false);
    $output = new BufferedOutput();

    $application->run(new ArrayInput(['command' => 'init', '--force' => true]), $output);

    expect(file_get_contents($this->project . '/lens.json'))
        ->not->toBe('{"paths":["custom"]}');
    expect($output->fetch())->toContain('wrote');
});

it('throws a clear error when the project root is not writable', function (): void {
    if (posix_geteuid() === 0) {
        $this->markTestSkipped('root user bypasses POSIX write permissions');
    }
    chmod($this->project, 0o555);

    $application = new Application();
    $application->setAutoExit(false);
    $output = new BufferedOutput();

    try {
        $exit = $application->run(new ArrayInput(['command' => 'init']), $output);

        expect($exit)->toBeGreaterThan(0);
        expect($output->fetch())->toContain('failed to write');
        expect(file_exists($this->project . '/lens.json'))->toBeFalse();
    } finally {
        chmod($this->project, 0o755);
    }
});
