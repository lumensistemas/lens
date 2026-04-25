<?php

declare(strict_types=1);

use LumenSistemas\Lens\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    $this->cwd = getcwd();
    $this->project = sys_get_temp_dir() . '/lens-feature-' . uniqid();
    mkdir($this->project . '/src', 0o755, true);
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

it('flags style violations and exits non-zero in cs-fixer-only check', function (): void {
    file_put_contents(
        $this->project . '/src/Demo.php',
        "<?php\nnamespace Demo;\nclass Hello{public function greet(\$n){return \"hi \".\$n;}}\n",
    );

    $exit = runLens(['check', '--using' => 'php-cs-fixer'], $output);

    expect($exit)->toBeGreaterThan(0);
    expect($output)
        ->toContain('php-cs-fixer')
        ->toContain('lens summary');
});

it('exits zero when the source is already clean', function (): void {
    file_put_contents(
        $this->project . '/src/Clean.php',
        "<?php\n\ndeclare(strict_types=1);\n\nnamespace Demo;\n\nfinal class Clean\n{\n    public function greet(string \$name): string\n    {\n        return 'hi ' . \$name;\n    }\n}\n",
    );

    $exit = runLens(['check', '--using' => 'php-cs-fixer'], $output);

    expect($exit)->toBe(0);
    expect($output)->toContain('lens summary');
});

it('honors --using to skip drivers entirely', function (): void {
    file_put_contents(
        $this->project . '/src/Demo.php',
        "<?php\nnamespace Demo;\nclass Hello{}\n",
    );

    $exit = runLens(['check', '--using' => 'unknown-driver'], $output);

    expect($exit)->toBe(0);
    expect($output)->not->toContain('php-cs-fixer');
    expect($output)->not->toContain('rector');
    expect($output)->not->toContain('phpstan');
});

it('runs all three drivers on a default check and reports each section', function (): void {
    file_put_contents(
        $this->project . '/src/Demo.php',
        "<?php\nnamespace Demo;\nclass Hello{public function greet(\$n){return \"hi \".\$n;}}\n",
    );

    $exit = runLens(['check'], $output);

    expect($exit)->toBeGreaterThan(0);
    expect($output)
        ->toContain('php-cs-fixer')
        ->toContain('rector')
        ->toContain('phpstan')
        ->toContain('lens summary');
})->group('slow');

function runLens(array $args, ?string &$output): int
{
    $application = new Application();
    $application->setAutoExit(false);
    $buffer = new BufferedOutput();
    $exit = $application->run(new ArrayInput($args), $buffer);
    $output = $buffer->fetch();

    return $exit;
}
