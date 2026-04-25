<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Drivers;

use LumenSistemas\Lens\Config\ProjectConfig;
use LumenSistemas\Lens\Process\Quietly;
use LumenSistemas\Lens\Process\Runner;
use LumenSistemas\Lens\Process\VendorPath;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class PhpStanDriver implements Driver
{
    public function __construct(private Runner $runner = new Runner()) {}

    public function name(): string
    {
        return 'phpstan';
    }

    public function supportsFix(): bool
    {
        return false;
    }

    public function run(
        Mode $mode,
        ProjectConfig $projectConfig,
        RunContext $runContext,
        OutputInterface $output,
    ): int {
        $runContext->ensureCacheDir();

        $vendor = VendorPath::vendor();
        $bin = $vendor.'/phpstan/phpstan/phpstan';
        $configPath = $this->writeMergedConfig($vendor, $projectConfig, $runContext);

        $command = [
            PHP_BINARY,
            $bin,
            'analyse',
            '--configuration='.$configPath,
            '--no-interaction',
            '--no-progress',
            '--memory-limit=512M',
        ];

        // Make the project's classes (Laravel itself in particular)
        // visible to phpstan and to larastan's bootstrap. Without
        // this, phpstan would only see lens's bundled vendor, where
        // larastan lives but Illuminate\Foundation\Application does
        // not — bootstrap blows up before any analysis runs.
        $projectAutoload = $runContext->projectRoot.'/vendor/autoload.php';

        if (file_exists($projectAutoload)) {
            $command[] = '--autoload-file='.$projectAutoload;
        }

        if ($runContext->ci) {
            $command[] = '--error-format=github';
        }

        if ($runContext->dirtyFiles !== null) {
            if ($runContext->dirtyFiles === []) {
                return 0;
            }

            foreach ($runContext->dirtyFiles as $file) {
                $command[] = $file;
            }
        } else {
            foreach ($projectConfig->paths() as $path) {
                $command[] = $path;
            }
        }

        return $this->runner->run($command, $runContext->projectRoot, $output);
    }

    private function writeMergedConfig(
        string $vendor,
        ProjectConfig $projectConfig,
        RunContext $runContext,
    ): string {
        $shipped = VendorPath::packageRoot().'/config/phpstan.neon';
        $larastan = $this->isLaravelProject($runContext->projectRoot)
            ? $vendor.'/larastan/larastan/extension.neon'
            : null;
        $baseline = $projectConfig->phpstanBaseline($runContext->projectRoot);

        $includes = [$shipped];

        if ($larastan !== null && file_exists($larastan)) {
            $includes[] = $larastan;
        }

        if ($baseline !== null) {
            $includes[] = $baseline;
        }

        $contents = "includes:\n";

        foreach ($includes as $include) {
            $contents .= '    - '.$include."\n";
        }

        $merged = $runContext->cacheDir().'/phpstan.neon';
        $written = Quietly::call(fn (): false|int => file_put_contents($merged, $contents));

        if ($written === false) {
            throw new RuntimeException("lens: failed to write merged phpstan config {$merged}");
        }

        return $merged;
    }

    private function isLaravelProject(string $projectRoot): bool
    {
        $composerFile = $projectRoot.'/composer.json';

        if (!file_exists($composerFile)) {
            return false;
        }

        $data = json_decode((string) file_get_contents($composerFile), associative: true);

        if (!is_array($data)) {
            return false;
        }

        $require = $data['require'] ?? [];

        return is_array($require) && array_key_exists('laravel/framework', $require);
    }
}
