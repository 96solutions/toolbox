<?php

declare(strict_types=1);

namespace nssolutions\Toolbox\Tests\Hook;

use Composer\IO\IOInterface;
use nssolutions\Toolbox\Hook\HookWriter;
use nssolutions\Toolbox\Tests\TestCase;

class HookWriterTest extends TestCase
{
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/' . uniqid('toolbox-test-', true);
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    // --- resolve() ---

    public function testResolveReturnsHooksDirWhenGitDirExists(): void
    {
        $hooksDir = $this->tempDir . '/.git/hooks';
        mkdir($hooksDir, 0755, true);

        $io = $this->createMock(IOInterface::class);
        $io->expects($this->never())
            ->method('ask');
        $io->expects($this->never())
            ->method('writeError');

        $result = (new HookWriter())->resolve($this->tempDir, $io);

        self::assertSame($hooksDir, $result);
    }

    public function testResolveReturnsCustomPathWhenGitDirMissing(): void
    {
        $customDir = $this->tempDir . '/custom-hooks';
        mkdir($customDir, 0755, true);

        $io = $this->createMock(IOInterface::class);
        $io->expects($this->once())
            ->method('ask')
            ->willReturn($customDir);
        $io->expects($this->once())
            ->method('writeError');

        $result = (new HookWriter())->resolve($this->tempDir, $io);

        self::assertSame($customDir, $result);
    }

    public function testResolveStripsTrailingSlashFromCustomPath(): void
    {
        $customDir = $this->tempDir . '/custom-hooks';
        mkdir($customDir, 0755, true);

        $io = $this->mockIo([$customDir . '/']);

        $result = (new HookWriter())->resolve($this->tempDir, $io);

        self::assertSame($customDir, $result);
    }

    public function testResolveReturnsNullWhenUserCancels(): void
    {
        $io = $this->mockIo(['']);

        $result = (new HookWriter())->resolve($this->tempDir, $io);

        self::assertNull($result);
    }

    public function testResolveReturnsNullWhenCustomPathDoesNotExist(): void
    {
        $io = $this->mockIo(['/nonexistent/path/hooks']);

        $result = (new HookWriter())->resolve($this->tempDir, $io);

        self::assertNull($result);
    }

    // --- handleExisting() ---

    public function testHandleExistingReturnsTrueWhenNoHookPresent(): void
    {
        $io = $this->createMock(IOInterface::class);
        $io->expects($this->never())
            ->method('ask');
        $io->expects($this->never())
            ->method('write');

        $result = (new HookWriter())->handleExisting($this->tempDir, $io);

        self::assertTrue($result);
    }

    public function testHandleExistingReturnsTrueOnOverwrite(): void
    {
        $this->createHookFile();

        $io = $this->mockIo(['o']);

        $result = (new HookWriter())->handleExisting($this->tempDir, $io);

        self::assertTrue($result);
        self::assertFileExists($this->tempDir . '/pre-commit');
    }

    public function testHandleExistingCreatesBackupAndReturnsTrueOnBackup(): void
    {
        $this->createHookFile('original content');

        $io = $this->mockIo(['b']);

        $result = (new HookWriter())->handleExisting($this->tempDir, $io);

        self::assertTrue($result);
        self::assertFileExists($this->tempDir . '/pre-commit.bak');
        self::assertSame('original content', file_get_contents($this->tempDir . '/pre-commit.bak'));
    }

    public function testHandleExistingReturnsFalseOnCancel(): void
    {
        $this->createHookFile('original content');

        $io = $this->mockIo(['c']);

        $result = (new HookWriter())->handleExisting($this->tempDir, $io);

        self::assertFalse($result);
        // Original hook untouched
        self::assertSame('original content', file_get_contents($this->tempDir . '/pre-commit'));
    }

    public function testHandleExistingDefaultsToCancelOnEmptyInput(): void
    {
        $this->createHookFile();

        $io = $this->mockIo(['']);

        $result = (new HookWriter())->handleExisting($this->tempDir, $io);

        self::assertFalse($result);
    }

    // --- write() ---

    public function testWriteCreatesHookFile(): void
    {
        (new HookWriter())->write($this->tempDir, '#!/usr/bin/env php');

        self::assertFileExists($this->tempDir . '/pre-commit');
    }

    public function testWriteStoresCorrectContent(): void
    {
        $content = '#!/usr/bin/env php' . PHP_EOL . 'echo "hello";';

        (new HookWriter())->write($this->tempDir, $content);

        self::assertSame($content, file_get_contents($this->tempDir . '/pre-commit'));
    }

    public function testWriteSetsExecutablePermissions(): void
    {
        (new HookWriter())->write($this->tempDir, '#!/usr/bin/env php');

        $perms = fileperms($this->tempDir . '/pre-commit') & 0777;
        self::assertSame(0755, $perms);
    }
}
