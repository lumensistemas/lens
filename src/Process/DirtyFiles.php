<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Process;

use RuntimeException;
use Symfony\Component\Process\Process;

final class DirtyFiles
{
    /**
     * Discovers PHP files changed vs. $base together with the local
     * working tree (unstaged + staged). An empty result means a
     * genuinely clean working tree — never a discovery failure: any
     * setup problem (no git, not a repo, missing base ref, diff
     * error) throws so --dirty cannot return false-green.
     *
     * @return list<string>
     */
    public static function relativeTo(string $projectRoot, string $base = 'origin/main'): array
    {
        self::requireGit();
        self::requireGitRepo($projectRoot);

        if (!self::refExists($projectRoot, $base)) {
            throw new RuntimeException("lens: --dirty base ref '{$base}' not found in {$projectRoot}; ".'fetch it (e.g. `git fetch origin main`) or pass a reachable --base.');
        }

        $files = [];
        $diffs = [
            ['diff', '--name-only', '--diff-filter=ACMRT', $base.'...HEAD'],
            ['diff', '--name-only', '--diff-filter=ACMRT'],
            ['diff', '--name-only', '--diff-filter=ACMRT', '--cached'],
        ];

        foreach ($diffs as $diff) {
            foreach (self::collect($projectRoot, $diff) as $file) {
                $files[$file] = true;
            }
        }

        return array_keys($files);
    }

    private static function requireGit(): void
    {
        $process = new Process(['git', '--version']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException('lens: --dirty requires `git` on PATH');
        }
    }

    private static function requireGitRepo(string $projectRoot): void
    {
        $process = new Process(['git', 'rev-parse', '--git-dir'], $projectRoot);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException("lens: --dirty must be run inside a git repository ({$projectRoot} is not one)");
        }
    }

    private static function refExists(string $projectRoot, string $ref): bool
    {
        $process = new Process(['git', 'rev-parse', '--verify', '--quiet', $ref.'^{commit}'], $projectRoot);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * @param list<string> $diffArgs
     *
     * @return list<string>
     */
    private static function collect(string $projectRoot, array $diffArgs): array
    {
        $process = new Process(['git', ...$diffArgs], $projectRoot);
        $process->run();

        if (!$process->isSuccessful()) {
            $cmd = 'git '.implode(' ', $diffArgs);
            $stderr = trim($process->getErrorOutput());

            throw new RuntimeException("lens: --dirty failed running `{$cmd}`".($stderr !== '' ? ": {$stderr}" : ''));
        }

        $files = [];

        foreach (preg_split('/\R/', trim($process->getOutput())) ?: [] as $line) {
            if ($line === '') {
                continue;
            }

            if (!str_ends_with($line, '.php')) {
                continue;
            }
            $files[] = $line;
        }

        return $files;
    }
}
