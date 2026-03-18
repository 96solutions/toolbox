<?php

declare(strict_types=1);

namespace nssolutions\Toolbox\Tests\Hook;

use nssolutions\Toolbox\Hook\StagedFiles;
use PHPUnit\Framework\TestCase;

class StagedFilesTest extends TestCase
{
    public function testGetShellExpressionReturnsGitDiffSubstitution(): void
    {
        $expression = (new StagedFiles())->getShellExpression();

        self::assertSame('$(git diff --cached --name-only --diff-filter=ACM)', $expression);
    }

    public function testGetFilterExpressionIncludesExtension(): void
    {
        $expression = (new StagedFiles())->getFilterExpression('php');

        self::assertStringContainsString("grep '\\.php$'", $expression);
    }

    public function testGetFilterExpressionIncludesGitCommand(): void
    {
        $expression = (new StagedFiles())->getFilterExpression('php');

        self::assertStringContainsString('git diff --cached --name-only --diff-filter=ACM', $expression);
    }

    public function testGetFilterExpressionUsesProvidedExtension(): void
    {
        $js  = (new StagedFiles())->getFilterExpression('js');
        $ts  = (new StagedFiles())->getFilterExpression('ts');

        self::assertStringContainsString("grep '\\.js$'", $js);
        self::assertStringContainsString("grep '\\.ts$'", $ts);
    }

    public function testGetFilterExpressionIsWrappedInCommandSubstitution(): void
    {
        $expression = (new StagedFiles())->getFilterExpression('php');

        self::assertStringStartsWith('$(', $expression);
        self::assertStringEndsWith(')', $expression);
    }
}
