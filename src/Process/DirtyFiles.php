<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Process;

use Symfony\Component\Process\Process;

final class DirtyFiles
{
    /**
     * @return list<string>
     */
    public static function relativeTo(string $projectRoot, string $base = 'origin/main'): array
    {
        $candidates = [
            ['git', 'diff', '--name-only', '--diff-filter=ACMRT', $base . '...HEAD'],
            ['git', 'diff', '--name-only', '--diff-filter=ACMRT'],
            ['git', 'diff', '--name-only', '--diff-filter=ACMRT', '--cached'],
        ];

        $files = [];

        foreach ($candidates as $candidate) {
            $process = new Process($candidate, $projectRoot);
            $process->run();

            if (! $process->isSuccessful()) {
                continue;
            }

            foreach (preg_split('/\R/', trim($process->getOutput())) ?: [] as $line) {
                if ($line === '') {
                    continue;
                }

                if (! str_ends_with($line, '.php')) {
                    continue;
                }
                $files[$line] = true;
            }
        }

        return array_keys($files);
    }
}
