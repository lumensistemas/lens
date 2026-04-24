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

        if (! file_exists($marker)) {
            if (! is_dir($cache) && ! mkdir($cache, 0o755, true) && ! is_dir($cache)) {
                throw new RuntimeException("lens: could not create cache dir {$cache}");
            }
            (new Phar($running))->extractTo($cache, null, true);
            file_put_contents($marker, $running);
        }

        return $cache;
    }

    private static function cacheDir(): string
    {
        $base = getenv('XDG_CACHE_HOME')
            ?: ((getenv('HOME') ?: sys_get_temp_dir()) . '/.cache');

        return rtrim($base, '/') . '/lens/' . Application::VERSION;
    }
}
