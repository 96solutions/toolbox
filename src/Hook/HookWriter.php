<?php

declare(strict_types=1);

namespace nssolutions\Toolbox\Hook;

use Composer\IO\IOInterface;

/**
 * Handles all filesystem operations for writing the pre-commit hook file.
 *
 * Responsible for locating the hooks directory, resolving conflicts with
 * an existing hook, and writing the final script with correct permissions.
 */
class HookWriter
{
    private const HOOK_FILE = 'pre-commit';

    /**
     * Resolves the path to the .git/hooks directory.
     *
     * Looks for .git/hooks under $projectRoot first. If not found, asks the
     * user to provide a custom path. Returns null if the directory cannot be
     * resolved or the user cancels.
     */
    public function resolve(string $projectRoot, IOInterface $io): ?string
    {
        $hooksDir = $projectRoot . '/.git/hooks';

        if (is_dir($hooksDir)) {
            return $hooksDir;
        }

        $io->writeError('<warning>Could not find .git/hooks/ in project root.</warning>');
        $custom = self::askString($io, 'Provide path to hooks directory (leave empty to cancel): ', '');

        if ($custom === '') {
            return null;
        }

        if (! is_dir($custom)) {
            $io->writeError('<error>Directory not found: ' . $custom . '</error>');

            return null;
        }

        return rtrim($custom, '/');
    }

    /**
     * Handles the case where a pre-commit hook already exists.
     *
     * Asks the user whether to overwrite, back up (rename to pre-commit.bak),
     * or cancel. Returns false if the installation should be aborted.
     */
    public function handleExisting(string $hooksDir, IOInterface $io): bool
    {
        $hookPath = $hooksDir . '/' . self::HOOK_FILE;

        if (! file_exists($hookPath)) {
            return true;
        }

        $io->write('<info>A pre-commit hook already exists.</info>');
        $action = self::askString(
            $io,
            'What would you like to do? [o]verwrite / [b]ackup and replace / [c]ancel [c]: ',
            'c'
        );

        switch (strtolower(trim($action))) {
            case 'o':
                return true;
            case 'b':
                rename($hookPath, $hookPath . '.bak');
                $io->write('<info>Existing hook backed up to pre-commit.bak</info>');

                return true;
            default:
                $io->write('Installation cancelled.');

                return false;
        }
    }

    /**
     * Writes the hook script to the hooks directory and makes it executable.
     */
    public function write(string $hooksDir, string $content): void
    {
        $hookPath = $hooksDir . '/' . self::HOOK_FILE;
        file_put_contents($hookPath, $content);
        chmod($hookPath, 0755);
    }

    private static function askString(IOInterface $io, string $question, string $default): string
    {
        $answer = $io->ask($question, $default);

        return is_string($answer) ? $answer : $default;
    }
}
