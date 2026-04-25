<?php

declare(strict_types=1);

use LumenSistemas\Lens\Application;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    $this->phar = dirname(__DIR__, 2) . '/builds/lens';

    if (! file_exists($this->phar)) {
        $this->markTestSkipped('builds/lens not built; run `composer run build` first');
    }

    $this->cacheHome = sys_get_temp_dir() . '/lens-phar-' . uniqid();
    $this->project = sys_get_temp_dir() . '/lens-phar-proj-' . uniqid();
    mkdir($this->cacheHome, 0o755, true);
    mkdir($this->project . '/src', 0o755, true);
    file_put_contents(
        $this->project . '/src/Demo.php',
        "<?php\n\ndeclare(strict_types=1);\n\nnamespace Demo;\n\nfinal class Demo {}\n",
    );
});

afterEach(function (): void {
    foreach ([$this->cacheHome, $this->project] as $dir) {
        if (is_dir($dir)) {
            rrmdirIfExists($dir);
        }
    }
});

it('extracts the bundled vendor tree to XDG_CACHE_HOME on first PHAR run', function (): void {
    $process = new Process(
        [$this->phar, 'phpstan'],
        $this->project,
        ['XDG_CACHE_HOME' => $this->cacheHome, 'HOME' => $this->cacheHome, 'PATH' => getenv('PATH')],
    );
    $process->run();

    $extracted = $this->cacheHome . '/lens/' . Application::VERSION;

    expect(is_dir($extracted))->toBeTrue('cache dir was not created');
    expect(file_exists($extracted . '/.extracted'))->toBeTrue('.extracted marker missing');
    expect(is_dir($extracted . '/vendor'))->toBeTrue('vendor/ missing from extracted cache');
    expect(is_dir($extracted . '/config'))->toBeTrue('config/ missing from extracted cache');
    expect(is_dir($extracted . '/src'))->toBeTrue('src/ missing from extracted cache');
    expect(file_exists($extracted . '/vendor/phpstan/phpstan/phpstan'))
        ->toBeTrue('phpstan shim missing from extracted vendor');
})->group('slow');

it('reuses the cache without re-extracting on subsequent runs', function (): void {
    $env = ['XDG_CACHE_HOME' => $this->cacheHome, 'HOME' => $this->cacheHome, 'PATH' => getenv('PATH')];

    (new Process([$this->phar, 'phpstan'], $this->project, $env))->run();

    $marker = $this->cacheHome . '/lens/' . Application::VERSION . '/.extracted';
    $firstMtime = filemtime($marker);

    sleep(1);
    (new Process([$this->phar, 'phpstan'], $this->project, $env))->run();

    expect(filemtime($marker))->toBe($firstMtime);
})->group('slow');

function rrmdirIfExists(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    foreach (scandir($path) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $full = $path . '/' . $entry;
        is_dir($full) && ! is_link($full) ? rrmdirIfExists($full) : unlink($full);
    }
    rmdir($path);
}
