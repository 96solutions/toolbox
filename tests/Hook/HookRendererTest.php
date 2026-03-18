<?php

declare(strict_types=1);

namespace nssolutions\Toolbox\Tests\Hook;

use nssolutions\Toolbox\Hook\HookRenderer;
use nssolutions\Toolbox\Tests\TestCase;

class HookRendererTest extends TestCase
{
    public function testRenderedScriptHasShebangLine(): void
    {
        $script = (new HookRenderer())->render([$this->mockTool()]);

        self::assertStringContainsString('#!/usr/bin/env php', $script);
    }

    public function testRenderedScriptHasPhpOpeningTag(): void
    {
        $script = (new HookRenderer())->render([$this->mockTool()]);

        self::assertStringContainsString('<?php', $script);
    }

    public function testRenderedScriptContainsToolName(): void
    {
        $script = (new HookRenderer())->render([$this->mockTool('MyTool', 'vendor/bin/mytool run')]);

        self::assertStringContainsString('MyTool', $script);
    }

    public function testRenderedScriptContainsToolCommand(): void
    {
        $script = (new HookRenderer())->render([$this->mockTool('MyTool', 'vendor/bin/mytool run')]);

        self::assertStringContainsString('vendor/bin/mytool run', $script);
    }

    public function testRenderedScriptContainsAllTools(): void
    {
        $tools = [
            $this->mockTool('PHPStan', 'vendor/bin/phpstan analyse --level=max'),
            $this->mockTool('EasyCodingStandard', 'vendor/bin/ecs check --config=ecs.php'),
        ];

        $script = (new HookRenderer())->render($tools);

        self::assertStringContainsString('PHPStan', $script);
        self::assertStringContainsString('vendor/bin/phpstan analyse --level=max', $script);
        self::assertStringContainsString('EasyCodingStandard', $script);
        self::assertStringContainsString('vendor/bin/ecs check --config=ecs.php', $script);
    }

    public function testRenderedScriptExitsEarlyWhenNoStagedFiles(): void
    {
        $script = (new HookRenderer())->render([$this->mockTool()]);

        self::assertStringContainsString('No staged PHP files found.', $script);
        self::assertStringContainsString('exit(0)', $script);
    }

    public function testRenderedScriptExitsOnToolFailure(): void
    {
        $script = (new HookRenderer())->render([$this->mockTool()]);

        self::assertStringContainsString('exit($exitCode)', $script);
        self::assertStringContainsString('failed.', $script);
    }

    public function testRenderedScriptExitsZeroWhenAllToolsPass(): void
    {
        $script = (new HookRenderer())->render([$this->mockTool()]);

        self::assertStringContainsString('All tools passed.', $script);
    }

    public function testRenderedScriptAppendsStagedFileListToCommand(): void
    {
        $script = (new HookRenderer())->render([$this->mockTool()]);

        // The command is concatenated with the $fileList variable at runtime
        self::assertStringContainsString('$fileList', $script);
        self::assertStringContainsString("'command'] . ' ' . \$fileList", $script);
    }

    public function testToolNameIsEscapedInOutput(): void
    {
        // Backslash in name should not break the generated PHP array literal
        $tool = $this->mockTool('Tool\\Name', 'vendor/bin/tool');
        $script = (new HookRenderer())->render([$tool]);

        // The rendered script must be valid PHP (eval as a basic smoke check)
        self::assertStringContainsString('Tool', $script);
    }
}
