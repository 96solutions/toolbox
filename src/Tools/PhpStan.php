<?php

declare(strict_types=1);

namespace nssolutions\Toolbox\Tools;

use Composer\IO\IOInterface;

/**
 * PHPStan static analysis tool.
 *
 * Prompts for an analysis level and one or more target paths,
 * then generates a `phpstan analyse` command for the pre-commit hook.
 */
class PhpStan implements ToolInterface
{
    /** Analysis level passed to --level (0-9 or "max"). */
    private string $level = 'max';

    /** @var string[] Target paths to analyse. */
    private array $paths = [];

    /** Resolved path to the phpstan binary. */
    private string $binary = 'vendor/bin/phpstan';

    private bool $configured = false;

    public function getName(): string
    {
        return 'PHPStan';
    }

    /**
     * Prompts for analysis level, target paths, and binary location.
     * Falls back to asking for a custom binary path if vendor/bin/phpstan is not found.
     */
    public function configure(IOInterface $io, string $projectRoot): void
    {
        $this->level = $io->ask('  Analysis level (0-9 or max) [max]: ', 'max') ?? 'max';

        $io->write('  Enter paths to analyse one by one, leave empty to finish.');
        while (true) {
            $path = $io->ask('  Path: ', '');
            if ($path === null || $path === '') {
                break;
            }
            $this->paths[] = $path;
        }

        $defaultBinary = $projectRoot . '/vendor/bin/phpstan';
        if (file_exists($defaultBinary)) {
            $this->binary = 'vendor/bin/phpstan';
        } else {
            $this->binary = $io->ask('  vendor/bin/phpstan not found. Provide binary path: ', 'phpstan') ?? 'phpstan';
        }

        $this->configured = true;
    }

    /**
     * Returns the phpstan analyse command with level and paths.
     * Staged files are appended by HookRenderer at render time.
     */
    public function getCommand(): string
    {
        $parts = [$this->binary, 'analyse', '--level=' . $this->level];

        if ($this->paths !== []) {
            foreach ($this->paths as $path) {
                $parts[] = $path;
            }
        }

        return implode(' ', $parts);
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }
}
