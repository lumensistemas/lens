<?php

declare(strict_types=1);

use LumenSistemas\Lens\Config\ProjectConfig;
use LumenSistemas\Lens\Drivers\Driver;
use LumenSistemas\Lens\Drivers\DriverSelection;
use LumenSistemas\Lens\Drivers\Mode;
use LumenSistemas\Lens\Drivers\RunContext;
use Symfony\Component\Console\Output\OutputInterface;

beforeEach(function (): void {
    $this->drivers = [
        fakeDriver('php-cs-fixer'),
        fakeDriver('rector'),
        fakeDriver('phpstan'),
    ];
});

it('returns every driver when --using is empty', function (): void {
    $selected = DriverSelection::fromUsing($this->drivers, '');

    expect($selected)->toBe($this->drivers);
});

it('returns every driver when --using is whitespace only', function (): void {
    $selected = DriverSelection::fromUsing($this->drivers, '   ');

    expect($selected)->toBe($this->drivers);
});

it('filters drivers down to the names listed in --using', function (): void {
    $selected = DriverSelection::fromUsing($this->drivers, 'phpstan,php-cs-fixer');

    expect(array_map(fn (Driver $d) => $d->name(), $selected))
        ->toBe(['phpstan', 'php-cs-fixer']);
});

it('preserves the order given in --using rather than the original list order', function (): void {
    $selected = DriverSelection::fromUsing($this->drivers, 'rector,phpstan,php-cs-fixer');

    expect(array_map(fn (Driver $d) => $d->name(), $selected))
        ->toBe(['rector', 'phpstan', 'php-cs-fixer']);
});

it('tolerates whitespace around comma-separated entries', function (): void {
    $selected = DriverSelection::fromUsing($this->drivers, '  rector ,  phpstan  ');

    expect(array_map(fn (Driver $d) => $d->name(), $selected))
        ->toBe(['rector', 'phpstan']);
});

it('silently drops names that do not match any driver', function (): void {
    $selected = DriverSelection::fromUsing($this->drivers, 'phpstan,unknown,rector');

    expect(array_map(fn (Driver $d) => $d->name(), $selected))
        ->toBe(['phpstan', 'rector']);
});

it('returns an empty list when no name matches', function (): void {
    $selected = DriverSelection::fromUsing($this->drivers, 'foo,bar');

    expect($selected)->toBe([]);
});

function fakeDriver(string $name): Driver
{
    return new class($name) implements Driver
    {
        public function __construct(private readonly string $name) {}

        public function name(): string
        {
            return $this->name;
        }

        public function supportsFix(): bool
        {
            return true;
        }

        public function run(
            Mode $mode,
            ProjectConfig $projectConfig,
            RunContext $runContext,
            OutputInterface $output,
        ): int {
            return 0;
        }
    };
}
