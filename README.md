# 96solutions/toolbox

A PHP library that provides interactive Composer scripts to install and configure Git pre-commit hooks.

## Requirements

- PHP >= 8.0
- Composer

## Installation

```bash
composer require 96solutions/toolbox
```

## Usage

Add the hook installer to your project's `composer.json` scripts:

```json
{
    "scripts": {
        "install-hooks": "96solutions\\Toolbox\\Scripts\\InstallHooks::install"
    }
}
```

Run whenever you want to set up or reconfigure the pre-commit hook:

```bash
composer run install-hooks
```

The script is **interactive** — it will guide you through selecting which checks to enable and configuring options for each one.

## How it works

1. Looks for `.git/hooks/` in the project root. If not found, asks you to provide the path or exits gracefully.
2. Presents an interactive menu to select which checks to install (e.g. PHPStan, EasyCodingStandard).
3. For each selected check, prompts for relevant options (paths, levels, config files, etc.).
4. If a pre-commit hook already exists, asks whether to overwrite, back it up, or cancel.
5. Writes a PHP script (`pre-commit`) into `.git/hooks/` and makes it executable.

The generated hook is a PHP CLI script (`#!/usr/bin/env php`) — consistent with the rest of the toolchain and easy to extend.

## Available checks

| Check | Description |
|-------|-------------|
| PHPStan | Static analysis at a configurable level |
| EasyCodingStandard | Code style checks with a configurable config file |

## Extensibility

The library is designed to be extended with additional checks. Each check is a self-contained class implementing a common interface, responsible for its interactive configuration and hook command generation.

## Development

### Setup

```bash
composer install
```

### Run in Docker (PHP 8.4)

```bash
docker build -t toolbox-php84 .docker/php8.4
docker run --rm -v $(pwd):/app toolbox-php84 composer install
docker run --rm -v $(pwd):/app toolbox-php84 vendor/bin/phpunit
```

### Code style

```bash
vendor/bin/ecs check src
vendor/bin/ecs check src --fix
```

### Static analysis

```bash
vendor/bin/phpstan analyse src
```

### Tests

```bash
vendor/bin/phpunit
```

## TODO

### Project structure

#### `src/Scripts/InstallHooks.php`
Composer script entry point.
- `public static function install(Event $event): void` — called via `composer run install-hooks`
- Retrieves `IOInterface` from `$event->getIO()` for all user interaction
- Resolves project root from `$event->getComposer()->getConfig()`
- Orchestrates the full flow: git check → hook conflict check → check selection → per-check configuration → write hook

#### `src/Checks/CheckInterface.php`
Contract all checks must implement:
- `getName(): string` — label shown in the selection menu
- `configure(IOInterface $io, string $projectRoot): void` — interactive prompts to collect user options
- `getCommand(): string` — returns the final shell command to embed in the hook (with staged files placeholder resolved at hook runtime)
- `isConfigured(): bool` — returns whether `configure()` has been successfully completed

#### `src/Checks/PhpStan.php`
Implements `CheckInterface`.
- `configure()`:
  - Ask for analysis level (0–9 or `max`, default: `max`)
  - Ask for one or more paths to analyse (e.g. `src`, repeat prompt until empty input)
  - Check if `vendor/bin/phpstan` exists; if not, ask user to provide binary path
- `getCommand()`: builds `vendor/bin/phpstan analyse --level={level} {paths} {staged_files}`

#### `src/Checks/EasyCodingStandard.php`
Implements `CheckInterface`.
- `configure()`:
  - Ask for config file path (default: `ecs.php`)
  - Check if `vendor/bin/ecs` exists; if not, ask user to provide binary path
- `getCommand()`: builds `vendor/bin/ecs check --config={config} {staged_files}`

#### `src/Hook/StagedFiles.php`
Responsible for providing staged file resolution for use inside the generated hook script.
- `getShellExpression(): string` — returns the shell snippet that expands to staged files at hook runtime: `$(git diff --cached --name-only --diff-filter=ACM)`
- `getFilterExpression(string $extensions): string` — returns a filtered variant piping through `grep` for specific extensions (e.g. `*.php`): `$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$')`

#### `src/Hook/HookRenderer.php`
Builds the final PHP hook script as a string.
- `render(CheckInterface[] $checks): string` — produces a complete `#!/usr/bin/env php` script that:
  - Resolves staged files via shell at runtime
  - Runs each check's command sequentially
  - Exits with a non-zero code on first failure, printing which check failed
  - Exits `0` if all checks pass

#### `src/Hook/HookWriter.php`
Handles all filesystem interaction for the hook file.
- `resolve(string $projectRoot, IOInterface $io): string` — finds `.git/hooks/` under `$projectRoot`; if not found, asks user to provide a path; returns the resolved hooks directory path
- `handleExisting(string $hookPath, IOInterface $io): bool` — if `pre-commit` already exists, asks user to overwrite / back up (renames to `pre-commit.bak`) / cancel; returns `false` on cancel
- `write(string $hookPath, string $content): void` — writes the script to `pre-commit` and sets `chmod 0755`

---

#### `tests/Checks/PhpStanTest.php`
- Verify `getCommand()` output with various level/path combinations
- Verify `configure()` sets correct state when given mocked IO responses
- Verify binary fallback prompt is triggered when `vendor/bin/phpstan` is absent

#### `tests/Checks/EasyCodingStandardTest.php`
- Verify `getCommand()` output with custom and default config paths
- Verify binary fallback prompt when `vendor/bin/ecs` is absent

#### `tests/Hook/HookRendererTest.php`
- Verify rendered script contains shebang line
- Verify each check's command appears in output
- Verify exit-on-failure logic is present in rendered script

#### `tests/Hook/HookWriterTest.php`
- Uses `sys_get_temp_dir()` fixture — no real `.git/hooks/` involved
- Verify hook file is written with correct content and `0755` permissions
- Verify backup behaviour renames existing hook to `pre-commit.bak`
- Verify cancel returns `false` and leaves existing hook untouched

#### `tests/Hook/StagedFilesTest.php`
- Verify `getShellExpression()` returns the expected `git diff` snippet
- Verify `getFilterExpression()` appends correct `grep` pipe for given extension

### Implementation order

- [ ] `CheckInterface`
- [ ] `StagedFiles`
- [ ] `PhpStan` + `EasyCodingStandard` checks
- [ ] `HookRenderer`
- [ ] `HookWriter`
- [ ] `InstallHooks` script
- [ ] Tests for each class

## License

MIT — see [LICENSE](LICENSE).
