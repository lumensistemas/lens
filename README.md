# lens

Opinionated PHP code-quality conventions for [Lumen
Sistemas](https://github.com/lumensistemas) products. One package
that wraps php-cs-fixer, Rector and PHPStan with shipped configs;
installing it gives a project the canonical Lumen rules, and
bumping its version rolls those rules across every product in
lockstep.

The reference points are Laravel Pint (single tool, opinionated,
baked-in config) and Tighten Duster (multi-tool orchestrator).
lens is closer to Pint in philosophy and closer to Duster in
shape.

## Install

Requires PHP 8.3+ and `git` on PATH (for `--dirty`).

```sh
composer require --dev lumensistemas/lens
```

The package installs a self-contained PHAR at `vendor/bin/lens`.
None of the wrapped tools (php-cs-fixer, Rector, PHPStan,
Larastan, symfony components) land in your project's `vendor/` —
they are bundled inside the PHAR and extracted on first run to
`~/.cache/lens/<version>/`. The cache is fingerprinted against the
PHAR's signature, so swapping `builds/lens` for a newer build
auto-invalidates without manual cleanup. There are no version
conflicts to resolve against your application's dependency tree.

## Use

```sh
vendor/bin/lens check            # run all linters in check mode
vendor/bin/lens fix              # apply automatic fixes, then verify with phpstan
vendor/bin/lens cs-fixer [--fix] # single tool, --fix toggles write mode
vendor/bin/lens rector   [--fix]
vendor/bin/lens phpstan
```

Useful flags:

```
--dirty       only files changed vs. the merge-base with main
--ci          GitHub-style annotations
--using=…     comma-separated subset, e.g. --using=phpstan,rector
              (an unknown name fails fast — no silent skip)
--base=<ref>  git base ref for --dirty (default origin/main)
```

`lens check` returns the worst exit code across drivers, so a
single non-zero result fails CI without you needing to chain
commands.

`--dirty` is strict: missing git, missing repo, or an unfetched
base ref each fail loud. CI configurations using shallow clones
should `git fetch origin <branch>` (or use
`actions/checkout@v4` with `fetch-depth: 0`).

## One-time setup in a new project

```sh
vendor/bin/lens init                  # write lens.json + phpstan baseline
vendor/bin/lens publish:workflow      # drop .github/workflows/lens.yml
```

`init` is opt-in. lens runs without `lens.json` — it auto-detects
the standard Laravel layout (`app`, `bootstrap`, `config`,
`database`, `resources`, `routes`, `tests`, `src`) and analyses
whichever of those exist in the project root.

## lens.json — the override surface

By design, `lens.json` only configures *boundaries*, never rules.
The rules themselves live inside the package and change for every
product when lens is bumped.

```json
{
    "paths": ["app", "database", "routes", "tests"],
    "phpstan": {
        "baseline": "phpstan-baseline.neon"
    }
}
```

| key                | purpose                                                |
| ------------------ | ------------------------------------------------------ |
| `paths`            | dirs each tool analyses (default: detected)            |
| `phpstan.baseline` | optional phpstan baseline file at the project root     |

The shipped configs already exclude the universal cases (`vendor`,
`storage`, `bootstrap/cache`, `node_modules`) globally — there is
no per-project `exclude` knob today.

Any other key is rejected with a clear error. If you find
yourself wanting to disable a rule, the path is to open a PR
against lens itself — not to override locally. That is the point.

## CI

`lens publish:workflow` writes a GitHub Actions workflow that
installs PHP, caches `vendor/` and `.lens/`, and runs `lens check
--ci`. Wire it into your pull-request branch protection.

## Convention philosophy

Lumen's operating principles include "conventions over decisions"
and "consistency over cleverness". lens exists so the same PHP
code-quality decisions don't get re-litigated per project, and so
a single `composer update lumensistemas/lens` propagates the
canonical config to every product on the same day.

The corollary: if you find a real exception that the convention
should permit, the right move is to update the convention, not to
work around it locally.

## Development

```sh
git clone https://github.com/lumensistemas/lens
cd lens
composer install
composer test         # pest, ~8s (4 subprocess-tagged tests)
composer lens         # run lens check against itself
composer run build    # rebuild builds/lens (the PHAR)
```

The PHAR is built from `composer-build.json` (runtime tools as
`require`, no dev) into a staged `build/` working dir, then
written to `builds/lens` and committed. CI rebuilds on every push
and verifies via the freshly-built PHAR rather than source.

## License

MIT. See [LICENSE](LICENSE).
