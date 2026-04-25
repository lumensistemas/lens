<?php

declare(strict_types=1);

use LumenSistemas\Lens\Output\Reporter;
use Symfony\Component\Console\Output\BufferedOutput;

it('returns exit 0 when every tool succeeds', function (): void {
    $output = new BufferedOutput();
    $reporter = new Reporter($output);

    $reporter->startTool('php-cs-fixer');
    $reporter->endTool('php-cs-fixer', 0);
    $reporter->startTool('rector');
    $reporter->endTool('rector', 0);

    expect($reporter->summarize())->toBe(0);
});

it('returns the worst non-zero exit code across tools', function (): void {
    $output = new BufferedOutput();
    $reporter = new Reporter($output);

    $reporter->endTool('php-cs-fixer', 8);
    $reporter->endTool('rector', 2);
    $reporter->endTool('phpstan', 1);

    expect($reporter->summarize())->toBe(8);
});

it('prints a per-tool summary line', function (): void {
    $output = new BufferedOutput();
    $reporter = new Reporter($output);

    $reporter->endTool('php-cs-fixer', 0);
    $reporter->endTool('phpstan', 1);
    $reporter->summarize();

    $rendered = $output->fetch();

    expect($rendered)->toContain('lens summary');
    expect($rendered)->toContain('php-cs-fixer');
    expect($rendered)->toContain('ok');
    expect($rendered)->toContain('phpstan');
    expect($rendered)->toContain('fail (1)');
});

it('emits GitHub Actions group markers in --ci mode', function (): void {
    $output = new BufferedOutput();
    $reporter = new Reporter($output, ci: true);

    $reporter->startTool('rector');
    $reporter->endTool('rector', 0);

    $rendered = $output->fetch();

    expect($rendered)->toContain('::group::rector');
    expect($rendered)->toContain('::endgroup::');
});

it('omits group markers in non-ci mode', function (): void {
    $output = new BufferedOutput();
    $reporter = new Reporter($output);

    $reporter->startTool('rector');
    $reporter->endTool('rector', 0);

    $rendered = $output->fetch();

    expect($rendered)->not->toContain('::group::');
    expect($rendered)->not->toContain('::endgroup::');
});
