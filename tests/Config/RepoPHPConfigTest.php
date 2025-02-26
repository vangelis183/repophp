<?php

namespace Vangelis\RepoPHP\Tests\Config;

use PHPUnit\Framework\TestCase;
use Vangelis\RepoPHP\Config\RepoPHPConfig;
use Vangelis\RepoPHP\Exceptions\TokenCounterException;

class RepoPHPConfigTest extends TestCase
{
    private string $tokenCounterPath;

    protected function setUp(): void
    {
        $this->tokenCounterPath = __DIR__ . '/../../bin/token-counter';
        if (! file_exists($this->tokenCounterPath)) {
            touch($this->tokenCounterPath);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tokenCounterPath)) {
            unlink($this->tokenCounterPath);
        }
    }

    public function testDefaultConfiguration(): void
    {
        $config = new RepoPHPConfig();

        $this->assertEquals(RepoPHPConfig::FORMAT_PLAIN, $config->getFormat());
        $this->assertEquals(RepoPHPConfig::ENCODING_CL100K, $config->getEncoding());
        $this->assertTrue($config->getRespectGitignore());
        $this->assertNotEmpty($config->getExcludePatterns());
    }

    public function testCustomConfiguration(): void
    {
        $config = new RepoPHPConfig(
            RepoPHPConfig::FORMAT_MARKDOWN,
            ['*.txt'],
            false,
            $this->tokenCounterPath,
            RepoPHPConfig::ENCODING_P50K
        );

        $this->assertEquals(RepoPHPConfig::FORMAT_MARKDOWN, $config->getFormat());
        $this->assertEquals(RepoPHPConfig::ENCODING_P50K, $config->getEncoding());
        $this->assertFalse($config->getRespectGitignore());
        $this->assertContains('*.txt', $config->getExcludePatterns());
    }

    public function testExcludePatternsIncludeDefaults(): void
    {
        $customPatterns = ['*.custom'];
        $config = new RepoPHPConfig(
            RepoPHPConfig::FORMAT_PLAIN,
            $customPatterns
        );

        $patterns = $config->getExcludePatterns();
        $this->assertContains('*.custom', $patterns);
        $this->assertContains('composer.lock', $patterns);
        $this->assertContains('.env', $patterns);
    }

    public function testSupportedFormats(): void
    {
        $this->assertContains(RepoPHPConfig::FORMAT_PLAIN, RepoPHPConfig::SUPPORTED_FORMATS);
        $this->assertContains(RepoPHPConfig::FORMAT_MARKDOWN, RepoPHPConfig::SUPPORTED_FORMATS);
        $this->assertContains(RepoPHPConfig::FORMAT_JSON, RepoPHPConfig::SUPPORTED_FORMATS);
        $this->assertContains(RepoPHPConfig::FORMAT_XML, RepoPHPConfig::SUPPORTED_FORMATS);
    }

    public function testSupportedEncodings(): void
    {
        $this->assertArrayHasKey(RepoPHPConfig::ENCODING_CL100K, RepoPHPConfig::SUPPORTED_ENCODINGS);
        $this->assertArrayHasKey(RepoPHPConfig::ENCODING_P50K, RepoPHPConfig::SUPPORTED_ENCODINGS);
        $this->assertArrayHasKey(RepoPHPConfig::ENCODING_R50K, RepoPHPConfig::SUPPORTED_ENCODINGS);
        $this->assertArrayHasKey(RepoPHPConfig::ENCODING_P50K_EDIT, RepoPHPConfig::SUPPORTED_ENCODINGS);
    }

    public function testTokenCounterBinaryNotFound(): void
    {
        if (file_exists($this->tokenCounterPath)) {
            unlink($this->tokenCounterPath);
        }

        $this->expectException(TokenCounterException::class);
        $this->expectExceptionMessage('Token counter binary not found');

        new RepoPHPConfig();
    }

    public function testDefaultExcludePatterns(): void
    {
        $patterns = RepoPHPConfig::getDefaultExcludePatterns();

        $this->assertContains('composer.lock', $patterns);
        $this->assertContains('.env', $patterns);
        $this->assertContains('.phpunit.cache', $patterns);
        $this->assertContains('docker-compose.override.yml', $patterns);
    }
}
