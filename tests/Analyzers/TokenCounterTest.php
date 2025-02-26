<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Tests\Analyzers;

use PHPUnit\Framework\TestCase;
use Vangelis\RepoPHP\Analyzers\TokenCounter;
use Vangelis\RepoPHP\Exceptions\TokenCounterException;

class TokenCounterTest extends TestCase
{
    private TokenCounter $tokenCounter;

    private string $testDir;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir().'/token-counter-test';
        if (! file_exists($this->testDir)) {
            mkdir($this->testDir);
        }

        // Create mock executable
        $mockExecutable = $this->testDir.'/mock-counter';
        file_put_contents($mockExecutable, '#!/bin/bash'.PHP_EOL.'echo "42"');
        chmod($mockExecutable, 0755);

        $this->tokenCounter = new TokenCounter($mockExecutable);
    }

    protected function tearDown(): void
    {
        // Cleanup test files
        array_map('unlink', glob($this->testDir.'/*'));
        rmdir($this->testDir);
    }

    public function testCountTokensForTextFile(): void
    {
        $testFile = $this->testDir.'/test.txt';
        file_put_contents($testFile, 'Test content');

        $result = $this->tokenCounter->countTokens($testFile, 'utf-8');
        $this->assertEquals(42, $result);
    }

    public function testCountTokensForBinaryFile(): void
    {
        $testFile = $this->testDir.'/test.bin';
        file_put_contents($testFile, pack('H*', 'FF'));

        $result = $this->tokenCounter->countTokens($testFile, 'utf-8');
        $this->assertEquals(0, $result);
    }

    public function testThrowsExceptionForInvalidExecutable(): void
    {
        $this->expectException(TokenCounterException::class);
        new TokenCounter('/invalid/path/to/executable');
    }

    public function testThrowsExceptionForFailedCommand(): void
    {
        // Create failing executable
        $failingExecutable = $this->testDir.'/failing-counter';
        file_put_contents($failingExecutable, '#!/bin/bash'.PHP_EOL.'exit 1');
        chmod($failingExecutable, 0755);

        // Create test file
        $testFile = $this->testDir.'/test.txt';
        file_put_contents($testFile, 'Test content');

        $this->expectException(TokenCounterException::class);
        $counter = new TokenCounter($failingExecutable);
        $counter->countTokens($testFile, 'utf-8');
    }
}
