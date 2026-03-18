<?php

declare(strict_types=1);

namespace nssolutions\Toolbox\Tests;

use Composer\IO\IOInterface;
use nssolutions\Toolbox\Tools\ToolInterface;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected string $tempDir;

    protected function createVendorBinary(string $name): void
    {
        mkdir($this->tempDir . '/vendor/bin', 0755, true);
        touch($this->tempDir . '/vendor/bin/' . $name);
    }

    /** @param list<string|null> $askResponses */
    protected function mockIo(array $askResponses): IOInterface
    {
        $io = $this->createMock(IOInterface::class);
        $io->expects($this->atLeastOnce())
            ->method('ask')
            ->willReturnOnConsecutiveCalls(...$askResponses);

        return $io;
    }

    protected function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . '/' . $entry;

            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    protected function createHookFile(string $content = ''): void
    {
        file_put_contents($this->tempDir . '/pre-commit', $content);
    }

    protected function mockTool(string $name = 'TestTool', string $command = 'vendor/bin/test run'): ToolInterface
    {
        $tool = $this->createMock(ToolInterface::class);
        $tool->expects($this->once())
            ->method('getName')
            ->willReturn($name);
        $tool->expects($this->once())
            ->method('getCommand')
            ->willReturn($command);

        return $tool;
    }
}
