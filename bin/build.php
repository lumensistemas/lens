<?php

declare(strict_types=1);

// Builds the lens PHAR. Box reads composer.json from its own
// working directory and offers no override, so we stage a tiny
// build/ tree:
//
//     build/composer.json -> ../composer-build.json
//     build/{src,config,stubs,bin} -> ../<dir>
//     build/box.json (generated, output redirected to ../builds/lens)
//     build/vendor/  (composer install --no-dev, runtime tools only)
//
// The host's dev vendor/ (which has Box) is reused via vendor/bin/box.
// The whole build/ tree is gitignored.

chdir(dirname(__DIR__));

$root = getcwd();
$build = $root . '/build';
$buildBox = $build . '/box.json';

run('rm', '-rf', $build);
run('mkdir', '-p', $build);

// Box does not follow symlinks, so the build tree is a real copy.
run('cp', '-a', $root . '/composer-build.json', $build . '/composer.json');
run('cp', '-a', $root . '/LICENSE', $build . '/LICENSE');
foreach (['src', 'config', 'stubs', 'bin'] as $dir) {
    run('cp', '-a', $root . '/' . $dir, $build . '/' . $dir);
}

file_put_contents($buildBox, json_encode([
    'main' => 'bin/lens',
    'output' => $root . '/builds/lens',
    'directories' => ['src', 'config', 'stubs'],
    'files' => ['LICENSE'],
    'compactors' => ['KevinGH\\Box\\Compactor\\Php'],
    'compression' => 'GZ',
    'banner' => 'lumensistemas/lens — opinionated PHP code-quality conventions for Lumen.',
    'force-autodiscovery' => true,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

run('composer', 'install', '--working-dir=' . $build, '--no-interaction', '--no-progress', '--no-dev');
run('php', '-d', 'memory_limit=-1', $root . '/vendor/bin/box', 'compile', '--working-dir=' . $build);
run('rm', '-rf', $build);

echo "build: builds/lens written\n";

function run(string ...$args): void
{
    $cmd = implode(' ', array_map(escapeshellarg(...), $args));
    passthru($cmd, $code);
    if ($code !== 0) {
        throw new RuntimeException("command failed (exit {$code}): {$cmd}");
    }
}
