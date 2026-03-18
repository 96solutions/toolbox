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
        "install-hooks": "nssolutions\\Toolbox\\Scripts\\InstallHooks::install"
    }
}
```

Run whenever you want to set up or reconfigure the pre-commit hook:

```bash
composer run install-hooks
```

The script is **interactive** — it will guide you through selecting which tools to enable and configuring options for each one.

## How it works

1. Looks for `.git/hooks/` in the project root. If not found, asks you to provide the path or exits gracefully.
2. Presents an interactive menu to select which tools to install (e.g. PHPStan, EasyCodingStandard).
3. For each selected tool, prompts for relevant options (paths, levels, config files, etc.).
4. If a pre-commit hook already exists, asks whether to overwrite, back it up, or cancel.
5. Writes a PHP script (`pre-commit`) into `.git/hooks/` and makes it executable.

The generated hook is a PHP CLI script (`#!/usr/bin/env php`) that resolves staged PHP files at runtime and runs each configured tool against them, exiting on the first failure.

## Available tools

| Tool | Description |
|------|-------------|
| PHPStan | Static analysis at a configurable level |
| EasyCodingStandard | Code style checks with a configurable config file |

## Extensibility

The library is designed to be extended with additional tools. Each tool is a self-contained class implementing `ToolInterface`, responsible for its interactive configuration and hook command generation.

### Project structure

```
src/
├── Scripts/InstallHooks.php       # Composer script entry point — orchestrates the interactive flow
├── Tools/
│   ├── ToolInterface.php          # Contract: getName(), configure(), getCommand(), isConfigured()
│   ├── PhpStan.php                # Prompts for level and paths, resolves vendor/bin/phpstan
│   └── EasyCodingStandard.php    # Prompts for config file path, resolves vendor/bin/ecs
└── Hook/
    ├── HookRenderer.php           # Builds the PHP pre-commit script from configured tools
    ├── HookWriter.php             # Resolves hooks dir, handles conflicts, writes + chmod 0755
    └── StagedFiles.php            # Shell expression helpers for staged file resolution
```

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

## License

MIT — see [LICENSE](LICENSE).
