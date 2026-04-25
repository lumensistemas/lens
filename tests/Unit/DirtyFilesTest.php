<?php

declare(strict_types=1);

use LumenSistemas\Lens\Process\DirtyFiles;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    $this->repo = sys_get_temp_dir() . '/lens-dirty-' . uniqid();
    mkdir($this->repo, 0o755, true);
    git($this->repo, 'init', '--initial-branch=main', '--quiet');
    git($this->repo, 'config', 'user.email', 'test@example.com');
    git($this->repo, 'config', 'user.name', 'Test');
    git($this->repo, 'config', 'commit.gpgsign', 'false');
});

afterEach(function (): void {
    if (is_dir($this->repo)) {
        rrmdir($this->repo);
    }
});

it('returns no files in a clean working tree', function (): void {
    file_put_contents($this->repo . '/Foo.php', "<?php\nclass Foo {}\n");
    git($this->repo, 'add', '.');
    git($this->repo, 'commit', '-m', 'initial', '--quiet');

    expect(DirtyFiles::relativeTo($this->repo))->toBe([]);
});

it('lists unstaged php files modified since the last commit', function (): void {
    file_put_contents($this->repo . '/Foo.php', "<?php\nclass Foo {}\n");
    file_put_contents($this->repo . '/Bar.php', "<?php\nclass Bar {}\n");
    git($this->repo, 'add', '.');
    git($this->repo, 'commit', '-m', 'initial', '--quiet');

    file_put_contents($this->repo . '/Foo.php', "<?php\nclass Foo {} // edit\n");

    expect(DirtyFiles::relativeTo($this->repo))->toBe(['Foo.php']);
});

it('lists staged-but-not-committed files', function (): void {
    file_put_contents($this->repo . '/Foo.php', "<?php\nclass Foo {}\n");
    git($this->repo, 'add', '.');
    git($this->repo, 'commit', '-m', 'initial', '--quiet');

    file_put_contents($this->repo . '/Bar.php', "<?php\nclass Bar {}\n");
    git($this->repo, 'add', 'Bar.php');

    expect(DirtyFiles::relativeTo($this->repo))->toContain('Bar.php');
});

it('filters out non-php changes', function (): void {
    file_put_contents($this->repo . '/Foo.php', "<?php\nclass Foo {}\n");
    file_put_contents($this->repo . '/README.md', "hello\n");
    git($this->repo, 'add', '.');
    git($this->repo, 'commit', '-m', 'initial', '--quiet');

    file_put_contents($this->repo . '/Foo.php', "<?php\nclass Foo {} // edit\n");
    file_put_contents($this->repo . '/README.md', "edit\n");

    expect(DirtyFiles::relativeTo($this->repo))->toBe(['Foo.php']);
});

it('lists files added on a branch when comparing against the base ref', function (): void {
    file_put_contents($this->repo . '/Foo.php', "<?php\nclass Foo {}\n");
    git($this->repo, 'add', '.');
    git($this->repo, 'commit', '-m', 'initial', '--quiet');

    git($this->repo, 'checkout', '-b', 'feature', '--quiet');
    file_put_contents($this->repo . '/New.php', "<?php\nclass NewClass {}\n");
    git($this->repo, 'add', '.');
    git($this->repo, 'commit', '-m', 'add new', '--quiet');

    expect(DirtyFiles::relativeTo($this->repo, 'main'))->toBe(['New.php']);
});

it('deduplicates files reported by both staged and unstaged diffs', function (): void {
    file_put_contents($this->repo . '/Foo.php', "<?php\nclass Foo {}\n");
    git($this->repo, 'add', '.');
    git($this->repo, 'commit', '-m', 'initial', '--quiet');

    file_put_contents($this->repo . '/Foo.php', "<?php\nclass Foo {} // staged\n");
    git($this->repo, 'add', 'Foo.php');
    file_put_contents($this->repo . '/Foo.php', "<?php\nclass Foo {} // unstaged\n");

    $files = DirtyFiles::relativeTo($this->repo);

    expect($files)->toBe(['Foo.php']);
});

function git(string $cwd, string ...$args): void
{
    $process = new Process(['git', ...$args], $cwd);
    $process->mustRun();
}

function rrmdir(string $path): void
{
    foreach (scandir($path) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $full = $path . '/' . $entry;
        is_dir($full) && ! is_link($full) ? rrmdir($full) : unlink($full);
    }
    rmdir($path);
}
