<?php

declare(strict_types=1);

namespace nssolutions\Toolbox\Tools;

use Composer\IO\IOInterface;

/**
 * Contract for a tool that can be installed as part of the pre-commit hook.
 *
 * Each tool is responsible for its own interactive configuration and for
 * generating the shell command that will be embedded in the hook script.
 */
interface ToolInterface
{
    /**
     * Returns the human-readable name shown in the selection menu.
     */
    public function getName(): string;

    /**
     * Runs interactive prompts to collect configuration options from the user.
     *
     * @param IOInterface $io          Composer IO for reading input and writing output.
     * @param string      $projectRoot Absolute path to the project root, used to locate vendor binaries.
     */
    public function configure(IOInterface $io, string $projectRoot): void;

    /**
     * Returns the shell command to run in the hook, without staged files.
     * HookRenderer appends the staged file list to this command at render time.
     */
    public function getCommand(): string;

    /**
     * Returns true if configure() has been successfully completed.
     */
    public function isConfigured(): bool;
}
