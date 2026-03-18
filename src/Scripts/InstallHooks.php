<?php

declare(strict_types=1);

namespace nssolutions\Toolbox\Scripts;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use nssolutions\Toolbox\Hook\HookRenderer;
use nssolutions\Toolbox\Hook\HookWriter;
use nssolutions\Toolbox\Tools\EasyCodingStandard;
use nssolutions\Toolbox\Tools\PhpStan;
use nssolutions\Toolbox\Tools\ToolInterface;

/**
 * Composer script entry point for installing the git pre-commit hook.
 *
 * Invoke via: composer run install-hooks
 */
class InstallHooks
{
    /**
     * Runs the interactive hook installation flow.
     *
     * Resolves the hooks directory, handles any existing hook, prompts the
     * user to select and configure tools, then writes the generated hook script.
     */
    public static function install(Event $event): void
    {
        $io = $event->getIO();
        $projectRoot = getcwd();

        $io->write('');
        $io->write('<info>Git pre-commit hook installer</info>');
        $io->write('');

        $writer = new HookWriter();
        $hooksDir = $writer->resolve($projectRoot, $io);

        if ($hooksDir === null) {
            $io->write('<comment>No hooks directory found. Exiting.</comment>');

            return;
        }

        if (!$writer->handleExisting($hooksDir, $io)) {
            return;
        }

        $tools = self::selectTools($io, $projectRoot);

        if ($tools === []) {
            $io->write('<comment>No tools selected. Nothing to install.</comment>');

            return;
        }

        $content = (new HookRenderer())->render($tools);
        $writer->write($hooksDir, $content);

        $io->write('');
        $io->write('<info>Pre-commit hook installed successfully.</info>');
    }

    /**
     * Presents the list of available tools, prompts the user to select some,
     * runs configure() on each selected tool, and returns the configured list.
     *
     * @return ToolInterface[]
     */
    private static function selectTools(IOInterface $io, string $projectRoot): array
    {
        $available = [
            new PhpStan(),
            new EasyCodingStandard(),
        ];

        $io->write('Available tools:');
        foreach ($available as $i => $tool) {
            $io->write(sprintf('  [%d] %s', $i + 1, $tool->getName()));
        }
        $io->write('');

        $input = $io->ask('Select tools to install (comma-separated numbers, e.g. 1,2): ', '');

        if ($input === null || $input === '') {
            return [];
        }

        $selected = [];
        foreach (array_map('trim', explode(',', $input)) as $index) {
            $i = (int) $index - 1;

            if (!isset($available[$i])) {
                $io->writeError('<warning>Unknown selection: ' . $index . '</warning>');
                continue;
            }

            $tool = $available[$i];
            $io->write('');
            $io->write('Configuring <info>' . $tool->getName() . '</info>...');
            $tool->configure($io, $projectRoot);
            $selected[] = $tool;
        }

        return $selected;
    }
}
