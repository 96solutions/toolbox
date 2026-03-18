<?php

declare(strict_types=1);

namespace nssolutions\Toolbox\Hook;

use nssolutions\Toolbox\Tools\ToolInterface;

/**
 * Renders the pre-commit hook PHP script from a list of configured tools.
 *
 * The generated script resolves staged PHP files at runtime, then runs each
 * tool's command with those files appended, exiting on the first failure.
 */
class HookRenderer
{
    /**
     * Renders a complete, executable PHP hook script for the given tools.
     *
     * @param ToolInterface[] $tools Configured tool instances to embed in the hook.
     */
    public function render(array $tools): string
    {
        $commandsCode = $this->buildCommandsCode($tools);

        return <<<HOOK
        #!/usr/bin/env php
        <?php

        declare(strict_types=1);

        \$files = array_filter(
            explode(PHP_EOL, trim(shell_exec('git diff --cached --name-only --diff-filter=ACM') ?? '')),
            static fn (string \$file): bool => \$file !== '' && str_ends_with(\$file, '.php'),
        );

        if (\$files === []) {
            echo 'No staged PHP files found.' . PHP_EOL;
            exit(0);
        }

        \$fileList = implode(' ', array_map('escapeshellarg', \$files));

        \$tools = [{$commandsCode}];

        foreach (\$tools as \$tool) {
            echo 'Running ' . \$tool['name'] . '...' . PHP_EOL;
            passthru(\$tool['command'] . ' ' . \$fileList, \$exitCode);
            if (\$exitCode !== 0) {
                echo \$tool['name'] . ' failed.' . PHP_EOL;
                exit(\$exitCode);
            }
        }

        echo 'All tools passed.' . PHP_EOL;
        exit(0);
        HOOK;
    }

    /**
     * Serialises the tool list into a PHP array literal for embedding in the hook script.
     *
     * @param ToolInterface[] $tools
     */
    private function buildCommandsCode(array $tools): string
    {
        $lines = [];
        foreach ($tools as $tool) {
            $name = addslashes($tool->getName());
            $command = addslashes($tool->getCommand());
            $lines[] = "['name' => '{$name}', 'command' => '{$command}']";
        }

        return implode(', ', $lines);
    }
}
