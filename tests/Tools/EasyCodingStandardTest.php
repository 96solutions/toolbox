<?php

declare(strict_types=1);

namespace nssolutions\Toolbox\Tests\Tools;

use nssolutions\Toolbox\Tests\TestCase;
use nssolutions\Toolbox\Tools\EasyCodingStandard;

class EasyCodingStandardTest extends TestCase
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
        self::assertSame('EasyCodingStandard', (new EasyCodingStandard())->getName());
    }

    public function testIsNotConfiguredByDefault(): void
    {
        self::assertFalse((new EasyCodingStandard())->isConfigured());
    }

    public function testGetCommandReturnsDefaultsBeforeConfigure(): void
    {
        self::assertSame('vendor/bin/ecs check --config=ecs.php', (new EasyCodingStandard())->getCommand());
    }

    public function testConfigureWithDefaultConfigFile(): void
    {
        $this->createVendorBinary('ecs');

        $io = $this->mockIo(['ecs.php']);

        $tool = new EasyCodingStandard();
        $tool->configure($io, $this->tempDir);

        self::assertTrue($tool->isConfigured());
        self::assertSame('vendor/bin/ecs check --config=ecs.php', $tool->getCommand());
    }

    public function testConfigureWithCustomConfigFile(): void
    {
        $this->createVendorBinary('ecs');

        $io = $this->mockIo(['config/ecs.php']);

        $tool = new EasyCodingStandard();
        $tool->configure($io, $this->tempDir);

        self::assertSame('vendor/bin/ecs check --config=config/ecs.php', $tool->getCommand());
    }

    public function testConfigureUsesDefaultConfigWhenInputIsNull(): void
    {
        $this->createVendorBinary('ecs');

        $io = $this->mockIo([null]);

        $tool = new EasyCodingStandard();
        $tool->configure($io, $this->tempDir);

        self::assertStringContainsString('--config=ecs.php', $tool->getCommand());
    }

    public function testConfigureUsesFallbackBinaryWhenVendorBinaryNotFound(): void
    {
        // No vendor/bin/ecs in tempDir
        $io = $this->mockIo(['ecs.php', '/usr/local/bin/ecs']);

        $tool = new EasyCodingStandard();
        $tool->configure($io, $this->tempDir);

        self::assertStringStartsWith('/usr/local/bin/ecs', $tool->getCommand());
    }

    public function testConfigureUsesFallbackDefaultWhenBinaryPromptReturnsNull(): void
    {
        $io = $this->mockIo(['ecs.php', null]);

        $tool = new EasyCodingStandard();
        $tool->configure($io, $this->tempDir);

        self::assertStringStartsWith('ecs', $tool->getCommand());
    }
}
