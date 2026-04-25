<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Drivers;

final class DriverSelection
{
    /**
     * Filters $drivers to those named in $using (comma-separated, by
     * Driver::name). Empty input keeps the original order. Unknown
     * names are silently skipped — the orchestrator owns whether
     * that should warn.
     *
     * @param list<Driver> $drivers
     *
     * @return list<Driver>
     */
    public static function fromUsing(array $drivers, string $using): array
    {
        $using = trim($using);

        if ($using === '') {
            return $drivers;
        }

        $names = array_map(trim(...), explode(',', $using));
        $byName = [];

        foreach ($drivers as $driver) {
            $byName[$driver->name()] = $driver;
        }

        $selected = [];

        foreach ($names as $name) {
            if (isset($byName[$name])) {
                $selected[] = $byName[$name];
            }
        }

        return $selected;
    }
}
