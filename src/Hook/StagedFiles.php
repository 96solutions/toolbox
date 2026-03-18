<?php

declare(strict_types=1);

namespace nssolutions\Toolbox\Hook;

/**
 * Provides shell expressions for resolving staged files at hook runtime.
 *
 * The expressions are intended to be embedded inside shell commands where
 * the shell will expand them via command substitution ($(...)).
 */
class StagedFiles
{
    private const GIT_COMMAND = 'git diff --cached --name-only --diff-filter=ACM';

    /**
     * Returns a shell expression that expands to all staged files.
     *
     * Example output: $(git diff --cached --name-only --diff-filter=ACM)
     */
    public function getShellExpression(): string
    {
        return '$(' . self::GIT_COMMAND . ')';
    }

    /**
     * Returns a shell expression that expands to staged files filtered by extension.
     *
     * Example: getFilterExpression('php') →
     *   $(git diff --cached --name-only --diff-filter=ACM | grep '\.php$')
     */
    public function getFilterExpression(string $extension): string
    {
        return sprintf("$(%s | grep '\\.%s$')", self::GIT_COMMAND, $extension);
    }
}
