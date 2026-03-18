<?php

declare(strict_types=1);

namespace nssolutions\Toolbox\Tools;

use Composer\IO\IOInterface;

/**
 * EasyCodingStandard code style tool.
 *
 * Prompts for a config file path, then generates an `ecs check` command
 * for the pre-commit hook.
 */
class EasyCodingStandard implements ToolInterface
{
    /** Path to the ECS config file passed to --config. */
    private string $configFile = 'ecs.php';

    /** Resolved path to the ecs binary. */
    private string $binary = 'vendor/bin/ecs';

    private bool $configured = false;

    public function getName(): string
    {
        return 'EasyCodingStandard';
    }

    /**
     * Prompts for config file path and binary location.
     * Falls back to asking for a custom binary path if vendor/bin/ecs is not found.
     */
    public function configure(IOInterface $io, string $projectRoot): void
    {
        $this->configFile = self::askString($io, '  Config file path [ecs.php]: ', 'ecs.php');

        $defaultBinary = $projectRoot . '/vendor/bin/ecs';
        if (file_exists($defaultBinary)) {
            $this->binary = 'vendor/bin/ecs';
        } else {
            $this->binary = self::askString($io, '  vendor/bin/ecs not found. Provide binary path: ', 'ecs');
        }

        $this->configured = true;
    }

    /**
     * Returns the ecs check command with the configured config file.
     * Staged files are appended by HookRenderer at render time.
     */
    public function getCommand(): string
    {
        return $this->binary . ' check --config=' . $this->configFile;
    }

    /**
     * Returns true if configure() has been successfully completed.
     */
    public function isConfigured(): bool
    {
        return $this->configured;
    }

    private static function askString(IOInterface $io, string $question, string $default): string
    {
        $answer = $io->ask($question, $default);

        return is_string($answer) ? $answer : $default;
    }
}
