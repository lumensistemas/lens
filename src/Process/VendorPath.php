<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Process;

use LumenSistemas\Lens\Application;
use Phar;
use RuntimeException;

/**
 * Resolves on-disk paths for files shipped inside lens, including
 * the bundled vendor/ tree. When lens runs from its PHAR, contents
 * are extracted once into a per-version user cache so subprocess
 * tools (rector, phpstan) and tool config files can be referenced
 * by real-filesystem paths — phar:// paths are not exec-able and
 * external binaries cannot read them.
 */
final class VendorPath
{
    public static function vendor(): string
    {
        return self::root() . '/vendor';
    }

    public static function packageRoot(): string
    {
        return self::root();
    }

    private static function root(): string
    {
        $running = Phar::running(false);

        if ($running === '') {
            return Application::packageRoot();
        }

        $cache = self::cacheDir();
        $marker = $cache . '/.extracted';
        $signature = self::signature($running);

        if (file_exists($marker) && file_get_contents($marker) === $signature) {
            return $cache;
        }

        // Stale cache (different PHAR build, or no marker yet). Wipe
        // and re-extract — VERSION isn't bumped on every shipped-config
        // change, so a content fingerprint is the only safe key.
        if (is_dir($cache)) {
            self::removeTree($cache);
        }

        $created = Quietly::call(fn (): bool => mkdir($cache, 0o755, true));

        if (! $created && ! is_dir($cache)) {
            throw new RuntimeException("lens: failed to create cache dir {$cache}");
        }
        (new Phar($running))->extractTo($cache, null, true);

        $written = Quietly::call(fn (): int|false => file_put_contents($marker, $signature));

        if ($written === false) {
            throw new RuntimeException("lens: failed to write extraction marker {$marker}");
        }

        return $cache;
    }

    private static function signature(string $pharPath): string
    {
        $sig = (new Phar($pharPath))->getSignature();

        return ($sig['hash_type'] ?? 'unknown') . ':' . ($sig['hash'] ?? '');
    }

    private static function cacheDir(): string
    {
        $base = getenv('XDG_CACHE_HOME')
            ?: ((getenv('HOME') ?: sys_get_temp_dir()) . '/.cache');

        return rtrim($base, '/') . '/lens/' . Application::VERSION;
    }

    private static function removeTree(string $path): void
    {
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.') {
                continue;
            }

            if ($entry === '..') {
                continue;
            }
            $full = $path . '/' . $entry;

            if (is_dir($full) && ! is_link($full)) {
                self::removeTree($full);

                continue;
            }

            if (! Quietly::call(fn (): bool => unlink($full))) {
                throw new RuntimeException("lens: failed to remove cached file {$full}");
            }
        }

        if (! Quietly::call(fn (): bool => rmdir($path))) {
            throw new RuntimeException("lens: failed to remove cache dir {$path}");
        }
    }
}
