<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Config;

use JsonException;
use RuntimeException;

final readonly class ProjectConfig
{
    private const array ALLOWED_TOP_LEVEL = ['paths', 'exclude', 'phpstan'];
    private const array ALLOWED_PHPSTAN = ['baseline'];
    private const array DEFAULT_PATH_CANDIDATES = ['app', 'src', 'database', 'routes', 'tests'];

    /**
     * @param list<string> $paths
     * @param list<string> $exclude
     */
    private function __construct(
        public array $paths,
        public array $exclude,
        private ?string $phpstanBaseline,
    ) {
    }

    public static function load(string $projectRoot): self
    {
        $configFile = $projectRoot . '/lens.json';

        if (! file_exists($configFile)) {
            return self::defaults($projectRoot);
        }

        try {
            $raw = json_decode(
                (string) file_get_contents($configFile),
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $e) {
            throw new RuntimeException("lens.json is not valid JSON: {$e->getMessage()}", $e->getCode(), $e);
        }

        if (! is_array($raw)) {
            throw new RuntimeException('lens.json must contain a JSON object.');
        }

        self::reject(array_values(array_diff(array_keys($raw), self::ALLOWED_TOP_LEVEL)), 'lens.json');

        $paths = self::stringList($raw, 'paths') ?? self::detectDefaultPaths($projectRoot);
        $exclude = self::stringList($raw, 'exclude') ?? [];
        $baseline = null;

        if (isset($raw['phpstan'])) {
            if (! is_array($raw['phpstan'])) {
                throw new RuntimeException('lens.json: "phpstan" must be an object.');
            }
            self::reject(array_values(array_diff(array_keys($raw['phpstan']), self::ALLOWED_PHPSTAN)), 'lens.json#phpstan');

            if (isset($raw['phpstan']['baseline'])) {
                if (! is_string($raw['phpstan']['baseline'])) {
                    throw new RuntimeException('lens.json: "phpstan.baseline" must be a string.');
                }
                $baseline = $raw['phpstan']['baseline'];
            }
        }

        return new self($paths, $exclude, $baseline);
    }

    public static function defaults(string $projectRoot): self
    {
        return new self(self::detectDefaultPaths($projectRoot), [], null);
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        return $this->paths;
    }

    /**
     * @return list<string>
     */
    public function exclude(): array
    {
        return $this->exclude;
    }

    public function phpstanBaseline(string $projectRoot): ?string
    {
        if ($this->phpstanBaseline === null) {
            return null;
        }

        $absolute = str_starts_with($this->phpstanBaseline, '/')
            ? $this->phpstanBaseline
            : $projectRoot . '/' . $this->phpstanBaseline;

        return file_exists($absolute) ? $absolute : null;
    }

    /**
     * @return list<string>
     */
    private static function detectDefaultPaths(string $projectRoot): array
    {
        $detected = [];

        foreach (self::DEFAULT_PATH_CANDIDATES as $candidate) {
            if (is_dir($projectRoot . '/' . $candidate)) {
                $detected[] = $candidate;
            }
        }

        return $detected !== [] ? $detected : ['.'];
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return list<string>|null
     */
    private static function stringList(array $raw, string $key): ?array
    {
        if (! array_key_exists($key, $raw)) {
            return null;
        }

        if (! is_array($raw[$key])) {
            throw new RuntimeException("lens.json: \"{$key}\" must be an array of strings.");
        }

        $values = [];

        foreach ($raw[$key] as $value) {
            if (! is_string($value)) {
                throw new RuntimeException("lens.json: \"{$key}\" must be an array of strings.");
            }
            $values[] = $value;
        }

        return $values;
    }

    /**
     * @param list<string> $unknown
     */
    private static function reject(array $unknown, string $where): void
    {
        if ($unknown === []) {
            return;
        }
        sort($unknown);
        $list = implode(', ', $unknown);

        throw new RuntimeException("{$where}: unknown key(s): {$list}");
    }
}
