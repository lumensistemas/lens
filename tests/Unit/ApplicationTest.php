<?php

declare(strict_types=1);

use LumenSistemas\Lens\Application;

it('registers every shipped command', function (): void {
    $application = new Application();
    $names = array_keys($application->all());

    expect($names)->toContain('check', 'fix', 'cs-fixer', 'rector', 'phpstan', 'init', 'publish:workflow');
});

it('exposes a stable VERSION constant', function (): void {
    expect(Application::VERSION)->toBeString()->not->toBe('');
});

it('packageRoot resolves to the repo root', function (): void {
    expect(file_exists(Application::packageRoot() . '/composer.json'))->toBeTrue();
    expect(file_exists(Application::packageRoot() . '/config/php-cs-fixer.php'))->toBeTrue();
});
