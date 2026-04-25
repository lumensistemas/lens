<?php

declare(strict_types=1);

// Stages a build/ subdir with composer-build.json (runtime tools
// only) and runs box from there. Box doesn't follow symlinks and
// has no `composer-json` override, hence the staged copy.

chdir(dirname(__DIR__));

$root = getcwd();
$build = $root.'/build';

run('rm', '-rf', $build);
run('mkdir', '-p', $build);

run('cp', '-a', $root.'/composer-build.json', $build.'/composer.json');
foreach (['LICENSE', 'README.md'] as $file) {
    run('cp', '-a', $root.'/'.$file, $build.'/'.$file);
}
foreach (['src', 'config', 'stubs', 'bin'] as $dir) {
    run('cp', '-a', $root.'/'.$dir, $build.'/'.$dir);
}

file_put_contents($build.'/box.json', json_encode([
    'main' => 'bin/lens',
    'output' => $root.'/builds/lens',
    'directories' => ['src', 'config', 'stubs'],
    'files' => ['LICENSE', 'README.md'],
    'compactors' => ['KevinGH\\Box\\Compactor\\Php'],
    'compression' => 'GZ',
    'banner' => 'lumensistemas/lens — opinionated PHP code-quality conventions for Lumen.',
    'force-autodiscovery' => true,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

run('composer', 'install', '--working-dir='.$build, '--no-interaction', '--no-progress', '--no-dev');
run('php', '-d', 'memory_limit=-1', $root.'/vendor/bin/box', 'compile', '--working-dir='.$build);
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
