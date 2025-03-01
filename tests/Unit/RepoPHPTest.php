<?php

namespace Vangelis\RepoPHP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Vangelis\RepoPHP\Config\RepoPHPConfig;
use Vangelis\RepoPHP\RepoPHP;

class RepoPHPTest extends TestCase
{
    private string $repositoryRoot;
    private string $outputPath;
    private ?OutputInterface $output;
    private string $tokenCounterPath;

    protected function setUp(): void
    {
        $this->output = new BufferedOutput();
        $this->repositoryRoot = sys_get_temp_dir() . '/repophp-test-' . uniqid();
        $this->outputPath = sys_get_temp_dir() . '/repophp-output-' . uniqid() . '.txt';

        mkdir($this->repositoryRoot, 0777, true);
        file_put_contents($this->repositoryRoot . '/test.php', '<?php echo "Hello World"; ?>');
        file_put_contents($this->repositoryRoot . '/.gitignore', 'ignored.php');
        file_put_contents($this->repositoryRoot . '/ignored.php', '<?php echo "Ignored file"; ?>');

        // Create mock token counter binary
        $this->tokenCounterPath = sys_get_temp_dir() . '/mock-token-counter-' . uniqid();
        file_put_contents($this->tokenCounterPath, '#!/bin/bash' . PHP_EOL . 'echo "42"');
        chmod($this->tokenCounterPath, 0755);
    }

    protected function tearDown(): void
    {
        // Add token counter cleanup
        if (file_exists($this->tokenCounterPath)) {
            unlink($this->tokenCounterPath);
        }

        // Rest of existing tearDown code
        if (file_exists($this->outputPath)) {
            unlink($this->outputPath);
        }

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

    public function testConstructor(): void
    {
        $repoPHP = new RepoPHP(
            $this->repositoryRoot,
            $this->outputPath,
            RepoPHPConfig::FORMAT_PLAIN,
            [],
            true,
            $this->output,
            RepoPHPConfig::ENCODING_CL100K
        );

        $this->assertInstanceOf(RepoPHP::class, $repoPHP);
    }

    public function testPack(): void
    {
        $repoPHP = new RepoPHP(
            $this->repositoryRoot,
            $this->outputPath,
            RepoPHPConfig::FORMAT_PLAIN,
            [],
            true,
            $this->output,
            RepoPHPConfig::ENCODING_CL100K,
            false, // compress
            $this->tokenCounterPath // Pass the mock token counter path
        );

        $repoPHP->pack();

        $this->assertFileExists($this->outputPath);
        $content = file_get_contents($this->outputPath);
        $this->assertStringContainsString('test.php', $content);
    }

    public function testPackWithGitignoreRespect(): void
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
            true,
            $this->output,
            RepoPHPConfig::ENCODING_CL100K,
            false,
            $this->tokenCounterPath
        );

        $repoPHP->pack();

        $content = file_get_contents($this->outputPath);
        $this->assertStringContainsString('test.php', $content);
        $this->assertStringNotContainsString('<?php echo "Ignored file"; ?>', $content);
    }

    public function testPackWithoutGitignoreRespect(): void
    {
        $repoPHP = new RepoPHP(
            $this->repositoryRoot,
            $this->outputPath,
            RepoPHPConfig::FORMAT_PLAIN,
            [],
            true,
            $this->output,
            RepoPHPConfig::ENCODING_CL100K,
            false,
            $this->tokenCounterPath
        );

        $repoPHP->pack();

        $content = file_get_contents($this->outputPath);
        $this->assertStringContainsString('test.php', $content);
        $this->assertStringContainsString('ignored.php', $content);
    }

    public function testPackWithExcludePatterns(): void
    {
        $repoPHP = new RepoPHP(
            $this->repositoryRoot,
            $this->outputPath,
            RepoPHPConfig::FORMAT_PLAIN,
            ['*.php'],
            false,
            $this->output,
            RepoPHPConfig::ENCODING_CL100K,
            false,
            $this->tokenCounterPath
        );

        $repoPHP->pack();

        $content = file_get_contents($this->outputPath);
        $this->assertStringNotContainsString('Hello World', $content);
    }
}
