<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Process;

/**
 * Runs a callable with a no-op error handler installed. Lets us
 * inspect the return value of filesystem syscalls (mkdir, copy,
 * unlink, file_put_contents) and throw a clean RuntimeException
 * without PHP's E_WARNING leaking through to stderr or to a test
 * runner's error handler.
 *
 * The @ operator alone is not enough: PHPUnit installs an error
 * handler that bypasses error_reporting() in some configurations.
 */
final class Quietly
{
    /**
     * @template T
     *
     * @param callable(): T $fn
     *
     * @return T
     */
    public static function call(callable $fn): mixed
    {
        set_error_handler(static fn (): bool => true);

        try {
            return $fn();
        } finally {
            restore_error_handler();
        }
    }
}
