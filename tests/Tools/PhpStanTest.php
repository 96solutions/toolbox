<?php

declare(strict_types=1);

namespace nssolutions\Toolbox\Tests\Tools;

use nssolutions\Toolbox\Tests\TestCase;
use nssolutions\Toolbox\Tools\PhpStan;

class PhpStanTest extends TestCase
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

    public function testGetName(): void
    {
        self::assertSame('PHPStan', (new PhpStan())->getName());
    }

    public function testIsNotConfiguredByDefault(): void
    {
        self::assertFalse((new PhpStan())->isConfigured());
    }

    public function testGetCommandReturnsDefaultsBeforeConfigure(): void
    {
        self::assertSame('vendor/bin/phpstan analyse --level=max', (new PhpStan())->getCommand());
    }

    public function testConfigureWithLevelAndPaths(): void
    {
        $this->createVendorBinary('phpstan');

        $io = $this->mockIo(['5', 'src', 'tests', '']);

        $tool = new PhpStan();
        $tool->configure($io, $this->tempDir);

        self::assertTrue($tool->isConfigured());
        self::assertSame('vendor/bin/phpstan analyse --level=5 src tests', $tool->getCommand());
    }

    public function testConfigureWithNoPaths(): void
    {
        $this->createVendorBinary('phpstan');

        $io = $this->mockIo(['max', '']);

        $tool = new PhpStan();
        $tool->configure($io, $this->tempDir);

        self::assertSame('vendor/bin/phpstan analyse --level=max', $tool->getCommand());
    }

    public function testConfigureUsesDefaultLevelWhenInputIsNull(): void
    {
        $this->createVendorBinary('phpstan');

        // ask() returning null should fall back to 'max'
        $io = $this->mockIo([null, '']);

        $tool = new PhpStan();
        $tool->configure($io, $this->tempDir);

        self::assertStringContainsString('--level=max', $tool->getCommand());
    }

    public function testConfigureUsesFallbackBinaryWhenVendorBinaryNotFound(): void
    {
        // No vendor/bin/phpstan in tempDir
        $io = $this->mockIo(['max', '', '/usr/local/bin/phpstan']);

        $tool = new PhpStan();
        $tool->configure($io, $this->tempDir);

        self::assertStringStartsWith('/usr/local/bin/phpstan', $tool->getCommand());
    }

    public function testConfigureUsesFallbackDefaultWhenBinaryPromptReturnsNull(): void
    {
        $io = $this->mockIo(['max', '', null]);

        $tool = new PhpStan();
        $tool->configure($io, $this->tempDir);

        self::assertStringStartsWith('phpstan', $tool->getCommand());
    }
}
