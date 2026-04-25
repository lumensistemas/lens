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
        return self::root().'/vendor';
    }

    public static function packageRoot(): string
    {
        return self::root();
    }

    /**
     * Refuse to recursively delete a cache path that is itself a
     * symlink, or whose canonical resolution lies outside the
     * expected lens cache root. Without this guard, an attacker (or
     * a misconfigured environment) could redirect the cache path at
     * an arbitrary directory and have removeTree wipe its contents.
     *
     * Public so the safety contract is testable in isolation; the
     * actual scrub still happens inside root().
     */
    public static function assertSafeForRemoval(string $cache): void
    {
        if (is_link($cache)) {
            throw new RuntimeException("lens: refusing to delete cache dir; {$cache} is a symlink");
        }

        $realCache = realpath($cache);
        $realRoot = realpath(self::cacheRoot());

        if ($realCache === false || $realRoot === false) {
            throw new RuntimeException("lens: refusing to delete cache dir; cannot resolve canonical path of {$cache}");
        }

        $expectedPrefix = $realRoot.'/lens/';

        if (!str_starts_with($realCache.'/', $expectedPrefix)) {
            throw new RuntimeException("lens: refusing to delete cache dir; {$cache} resolves outside the lens cache root ({$expectedPrefix})");
        }
    }

    private static function root(): string
    {
        $running = Phar::running(false);

        if ($running === '') {
            return Application::packageRoot();
        }

        $cache = self::cacheDir();
        $marker = $cache.'/.extracted';
        $signature = self::signature($running);

        if (file_exists($marker) && file_get_contents($marker) === $signature) {
            return $cache;
        }

        // Stale cache (different PHAR build, or no marker yet). Wipe
        // and re-extract — VERSION isn't bumped on every shipped-config
        // change, so a content fingerprint is the only safe key.
        if (is_dir($cache) || is_link($cache)) {
            self::assertSafeForRemoval($cache);
            self::removeTree($cache);
        }

        $created = Quietly::call(fn (): bool => mkdir($cache, 0o755, true));

        if (!$created && !is_dir($cache)) {
            throw new RuntimeException("lens: failed to create cache dir {$cache}");
        }
        (new Phar($running))->extractTo($cache, null, true);

        $written = Quietly::call(fn (): false|int => file_put_contents($marker, $signature));

        if ($written === false) {
            throw new RuntimeException("lens: failed to write extraction marker {$marker}");
        }

        return $cache;
    }

    private static function signature(string $pharPath): string
    {
        $sig = (new Phar($pharPath))->getSignature();

        return ($sig['hash_type'] ?? 'unknown').':'.($sig['hash'] ?? '');
    }

    private static function cacheDir(): string
    {
        return rtrim(self::cacheRoot(), '/').'/lens/'.Application::VERSION;
    }

    private static function cacheRoot(): string
    {
        return getenv('XDG_CACHE_HOME')
            ?: ((getenv('HOME') ?: sys_get_temp_dir()).'/.cache');
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
            $full = $path.'/'.$entry;

            if (is_dir($full) && !is_link($full)) {
                self::removeTree($full);

                continue;
            }

            if (!Quietly::call(fn (): bool => unlink($full))) {
                throw new RuntimeException("lens: failed to remove cached file {$full}");
            }
        }

        if (!Quietly::call(fn (): bool => rmdir($path))) {
            throw new RuntimeException("lens: failed to remove cache dir {$path}");
        }
    }
}
