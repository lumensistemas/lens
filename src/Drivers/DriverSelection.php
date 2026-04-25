<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Drivers;

use InvalidArgumentException;

final class DriverSelection
{
    /**
     * Filters $drivers to those named in $using (comma-separated, by
     * Driver::name). Empty input keeps the original order. Any
     * unknown name throws — silently skipping a typo could let CI
     * pass without running anything (e.g. --using=phpstna).
     *
     * @param list<Driver> $drivers
     *
     * @throws InvalidArgumentException when any requested name does
     *                                  not match a known driver
     *
     * @return list<Driver>
     */
    public static function fromUsing(array $drivers, string $using): array
    {
        $names = array_values(array_filter(
            array_map(trim(...), explode(',', $using)),
            fn (string $name): bool => $name !== '',
        ));

        if ($names === []) {
            return $drivers;
        }

        $byName = [];

        foreach ($drivers as $driver) {
            $byName[$driver->name()] = $driver;
        }

        $unknown = array_values(array_diff($names, array_keys($byName)));

        if ($unknown !== []) {
            $known = implode(', ', array_keys($byName));
            $bad = implode(', ', $unknown);

            throw new InvalidArgumentException("lens: unknown driver(s) in --using: {$bad}. Known: {$known}");
        }

        return array_map(fn (string $name): Driver => $byName[$name], $names);
    }
}
