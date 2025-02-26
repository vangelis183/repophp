<?php

namespace Vangelis\RepoPHP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vangelis\RepoPHP\Config\RepoPHPConfig;
use Vangelis\RepoPHP\RepoPHP;

class RepoPHPTest extends TestCase
{
    private string $repositoryRoot;
    private string $outputPath;
    private string $tokenCounterPath;

    protected function setUp(): void
    {
        $this->repositoryRoot = sys_get_temp_dir() . '/repophp-test-' . uniqid();
        $this->outputPath = sys_get_temp_dir() . '/repophp-output-' . uniqid() . '.txt';
        $this->tokenCounterPath = sys_get_temp_dir() . '/tokencounter-' . uniqid();

        mkdir($this->repositoryRoot, 0777, true);
        file_put_contents($this->repositoryRoot . '/test.php', '<?php echo "Hello World"; ?>');
        file_put_contents($this->repositoryRoot . '/.gitignore', 'ignored.php');
        file_put_contents($this->repositoryRoot . '/ignored.php', '<?php echo "Ignored file"; ?>');

        // Create mock token counter binary
        file_put_contents($this->tokenCounterPath, '#!/bin/bash' . PHP_EOL . 'echo "10"');
        chmod($this->tokenCounterPath, 0755);

        // Create bin directory in vendor and copy token counter
        $vendorBinDir = dirname(__DIR__, 3) . '/bin';
        if (! is_dir($vendorBinDir)) {
            mkdir($vendorBinDir, 0777, true);
        }
        copy($this->tokenCounterPath, $vendorBinDir . '/tokencounter');
        chmod($vendorBinDir . '/tokencounter', 0755);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->outputPath)) {
            unlink($this->outputPath);
        }

        if (file_exists($this->repositoryRoot . '/test.php')) {
            unlink($this->repositoryRoot . '/test.php');
        }

        if (file_exists($this->repositoryRoot . '/.gitignore')) {
            unlink($this->repositoryRoot . '/.gitignore');
        }

        if (file_exists($this->repositoryRoot . '/ignored.php')) {
            unlink($this->repositoryRoot . '/ignored.php');
        }

        if (file_exists($this->tokenCounterPath)) {
            unlink($this->tokenCounterPath);
        }

        // Clean up vendor bin token counter
        $vendorTokenCounter = dirname(__DIR__, 3) . '/bin/tokencounter';
        if (file_exists($vendorTokenCounter)) {
            unlink($vendorTokenCounter);
        }

        if (is_dir($this->repositoryRoot)) {
            $this->removeDirectory($this->repositoryRoot);
        }
    }

    private function removeDirectory(string $path): void
    {
        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $path . '/' . $file;
            if (is_dir($filePath)) {
                $this->removeDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }
        rmdir($path);
    }

    public function testConstructor()
    {
        $repoPHP = new RepoPHP(
            $this->repositoryRoot,
            $this->outputPath,
            RepoPHPConfig::FORMAT_PLAIN,
            [],
            true,
            null,
            RepoPHPConfig::ENCODING_CL100K
        );

        $this->assertInstanceOf(RepoPHP::class, $repoPHP);
    }

    public function testPack()
    {
        $repoPHP = new RepoPHP(
            $this->repositoryRoot,
            $this->outputPath,
            RepoPHPConfig::FORMAT_PLAIN,
            [],
            true,
            null,
            RepoPHPConfig::ENCODING_CL100K
        );

        $repoPHP->pack();

        $this->assertFileExists($this->outputPath);
        $content = file_get_contents($this->outputPath);
        $this->assertStringContainsString('test.php', $content);
    }

    public function testPackWithGitignoreRespect()
    {
        // Initialize Git repository
        exec('git -C ' . escapeshellarg($this->repositoryRoot) . ' init');
        exec('git -C ' . escapeshellarg($this->repositoryRoot) . ' config user.email "test@example.com"');
        exec('git -C ' . escapeshellarg($this->repositoryRoot) . ' config user.name "Test User"');

        // Add and commit .gitignore first
        exec('git -C ' . escapeshellarg($this->repositoryRoot) . ' add .gitignore');
        exec('git -C ' . escapeshellarg($this->repositoryRoot) . ' commit -m "Add gitignore"');

        // Add remaining files
        exec('git -C ' . escapeshellarg($this->repositoryRoot) . ' add .');
        exec('git -C ' . escapeshellarg($this->repositoryRoot) . ' commit -m "Add files"');

        $repoPHP = new RepoPHP(
            $this->repositoryRoot,
            $this->outputPath,
            RepoPHPConfig::FORMAT_PLAIN,
            [],
            true
        );

        $repoPHP->pack();

        $content = file_get_contents($this->outputPath);
        $this->assertStringContainsString('test.php', $content);
        // Check for the actual ignored file content instead of the pattern
        $this->assertStringNotContainsString('<?php echo "Ignored file"; ?>', $content);
    }

    public function testPackWithoutGitignoreRespect()
    {
        $repoPHP = new RepoPHP(
            $this->repositoryRoot,
            $this->outputPath,
            RepoPHPConfig::FORMAT_PLAIN,
            [],
            false // gitignore nicht beachten
        );

        $repoPHP->pack();

        $content = file_get_contents($this->outputPath);
        $this->assertStringContainsString('test.php', $content);
        $this->assertStringContainsString('ignored.php', $content);
    }

    public function testPackWithExcludePatterns()
    {
        $repoPHP = new RepoPHP(
            $this->repositoryRoot,
            $this->outputPath,
            RepoPHPConfig::FORMAT_PLAIN,
            ['*.php'] // PHP-Dateien ausschließen
        );

        $repoPHP->pack();

        $content = file_get_contents($this->outputPath);
        $this->assertStringNotContainsString('Hello World', $content);
    }
}
