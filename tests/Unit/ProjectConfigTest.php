<?php

declare(strict_types=1);

use LumenSistemas\Lens\Config\ProjectConfig;

beforeEach(function (): void {
    $this->root = sys_get_temp_dir() . '/lens-test-' . uniqid();
    mkdir($this->root, 0o755, true);
});

afterEach(function (): void {
    if (is_dir($this->root)) {
        $rrmdir = function (string $path) use (&$rrmdir): void {
            foreach (scandir($path) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $full = $path . '/' . $entry;
                is_dir($full) ? $rrmdir($full) : unlink($full);
            }
            rmdir($path);
        };
        $rrmdir($this->root);
    }
});

describe('default detection', function (): void {
    it('returns existing Laravel-style dirs when no lens.json is present', function (): void {
        mkdir($this->root . '/app');
        mkdir($this->root . '/routes');

        $config = ProjectConfig::load($this->root);

        expect($config->paths())->toBe(['app', 'routes']);
        expect($config->phpstanBaseline($this->root))->toBeNull();
    });

    it('falls back to the project root when no candidate directory exists', function (): void {
        $config = ProjectConfig::load($this->root);

        expect($config->paths())->toBe(['.']);
    });
});

describe('lens.json parsing', function (): void {
    it('reads paths from lens.json', function (): void {
        file_put_contents($this->root . '/lens.json', json_encode([
            'paths' => ['custom-src', 'tests'],
        ]));

        $config = ProjectConfig::load($this->root);

        expect($config->paths())->toBe(['custom-src', 'tests']);
    });

    it('throws when lens.json is not valid JSON', function (): void {
        file_put_contents($this->root . '/lens.json', '{not json');

        ProjectConfig::load($this->root);
    })->throws(RuntimeException::class, 'is not valid JSON');

    it('throws when lens.json is not an object', function (): void {
        file_put_contents($this->root . '/lens.json', '"a string"');

        ProjectConfig::load($this->root);
    })->throws(RuntimeException::class, 'must contain a JSON object');

    it('rejects unknown top-level keys', function (): void {
        file_put_contents($this->root . '/lens.json', json_encode([
            'paths' => ['src'],
            'rules' => ['no_unused_imports' => false],
        ]));

        ProjectConfig::load($this->root);
    })->throws(RuntimeException::class, 'unknown key(s): rules');

    it('rejects exclude as an unknown top-level key', function (): void {
        file_put_contents($this->root . '/lens.json', json_encode([
            'exclude' => ['legacy'],
        ]));

        ProjectConfig::load($this->root);
    })->throws(RuntimeException::class, 'unknown key(s): exclude');

    it('rejects unknown phpstan.* keys', function (): void {
        file_put_contents($this->root . '/lens.json', json_encode([
            'phpstan' => ['level' => 9, 'baseline' => 'foo.neon'],
        ]));

        ProjectConfig::load($this->root);
    })->throws(RuntimeException::class, 'unknown key(s): level');

    it('requires paths to be an array of strings', function (): void {
        file_put_contents($this->root . '/lens.json', json_encode([
            'paths' => ['app', 42, 'tests'],
        ]));

        ProjectConfig::load($this->root);
    })->throws(RuntimeException::class, '"paths" must be an array of strings');

    it('requires phpstan to be an object', function (): void {
        file_put_contents($this->root . '/lens.json', json_encode([
            'phpstan' => 'something',
        ]));

        ProjectConfig::load($this->root);
    })->throws(RuntimeException::class, '"phpstan" must be an object');

    it('requires phpstan.baseline to be a string', function (): void {
        file_put_contents($this->root . '/lens.json', json_encode([
            'phpstan' => ['baseline' => 123],
        ]));

        ProjectConfig::load($this->root);
    })->throws(RuntimeException::class, '"phpstan.baseline" must be a string');
});

describe('phpstan baseline resolution', function (): void {
    it('returns null when no baseline is configured', function (): void {
        file_put_contents($this->root . '/lens.json', json_encode([]));

        $config = ProjectConfig::load($this->root);

        expect($config->phpstanBaseline($this->root))->toBeNull();
    });

    it('resolves a relative baseline path against the project root', function (): void {
        file_put_contents($this->root . '/baseline.neon', 'parameters: {}');
        file_put_contents($this->root . '/lens.json', json_encode([
            'phpstan' => ['baseline' => 'baseline.neon'],
        ]));

        $config = ProjectConfig::load($this->root);

        expect($config->phpstanBaseline($this->root))
            ->toBe($this->root . '/baseline.neon');
    });

    it('returns null when the configured baseline file does not exist', function (): void {
        file_put_contents($this->root . '/lens.json', json_encode([
            'phpstan' => ['baseline' => 'missing.neon'],
        ]));

        $config = ProjectConfig::load($this->root);

        expect($config->phpstanBaseline($this->root))->toBeNull();
    });

    it('uses an absolute baseline path verbatim', function (): void {
        $absolute = $this->root . '/abs.neon';
        file_put_contents($absolute, 'parameters: {}');
        file_put_contents($this->root . '/lens.json', json_encode([
            'phpstan' => ['baseline' => $absolute],
        ]));

        $config = ProjectConfig::load($this->root);

        expect($config->phpstanBaseline($this->root))->toBe($absolute);
    });
});
