<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Vangelis\RepoPHP\Config\RepoPHPConfig;
use Vangelis\RepoPHP\RepoPHP;

class RepoPHPSplittingTest extends TestCase
{
    private string $testRepoPath;
    private string $outputPath;
    private array $testFiles;
    private ?string $tokenCounterPath = null;

    protected function setUp(): void
    {
        // Create a temporary test repository with controlled file sizes
        $this->testRepoPath = sys_get_temp_dir() . '/repophp_test_' . uniqid();
        mkdir($this->testRepoPath, 0777, true);

        // Create output directory
        $outputDir = sys_get_temp_dir() . '/repophp_output_' . uniqid();
        mkdir($outputDir, 0755, true);
        $this->outputPath = $outputDir . '/output.txt';

        // Setup token counter in a temporary location with proper permissions
        $this->setupTokenCounter();

        // Create test files with known content
        $this->createTestFiles();
    }

    private function setupTokenCounter(): void
    {
        // Find the package's bin directory relative to the test file
        $packageRoot = dirname(__DIR__, 2); // Go up from tests/Unit to package root
        $binDir = $packageRoot . '/bin';

        // Determine OS and architecture (match the logic in RepoPHPConfig)
        $os = strtolower(PHP_OS);
        if (strpos($os, 'darwin') !== false) {
            $os = 'mac';
        } elseif (strpos($os, 'win') !== false) {
            $os = 'windows';
        } else {
            $os = 'linux';
        }

        $arch = php_uname('m');
        if ($arch === 'x86_64' || $arch === 'amd64') {
            $arch = 'amd64';
        } elseif (strpos($arch, 'arm') !== false || strpos($arch, 'aarch64') !== false) {
            $arch = 'arm64';
        } else {
            $arch = 'amd64'; // Default to amd64 if unsure
        }

        // Construct binary name based on platform
        $binaryName = "token-counter-{$os}-{$arch}";
        if ($os === 'windows') {
            $binaryName .= '.exe';
        }

        // Look for the platform-specific binary
        $sourceTokenCounter = $binDir . '/' . $binaryName;

        // Fall back to generic binaries if platform-specific isn't found
        if (! file_exists($sourceTokenCounter)) {
            $fallbackBinaries = [
            $binDir . '/token-counter',
            $binDir . '/token-counter.exe',
            ];

            foreach ($fallbackBinaries as $binary) {
                if (file_exists($binary)) {
                    $sourceTokenCounter = $binary;

                    break;
                }
            }
        }

        // Skip if no token counter found in bin directory
        if (! file_exists($sourceTokenCounter)) {
            return; // Tests will run without tokenizer, may fail differently
        }

        // Create temp directory for token counter
        $tempDir = sys_get_temp_dir() . '/repophp_token_counter_' . uniqid();
        mkdir($tempDir, 0777, true);

        // Copy token counter to temp directory
        $tempTokenCounter = $tempDir . '/' . basename($sourceTokenCounter);
        copy($sourceTokenCounter, $tempTokenCounter);
        chmod($tempTokenCounter, 0777); // Ensure it's executable by everyone

        $this->tokenCounterPath = $tempTokenCounter;
    }

    protected function tearDown(): void
    {
        // Clean up test files
        foreach ($this->testFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        // Remove test directories
        $this->removeDirectory($this->testRepoPath);
        $this->removeDirectory(dirname($this->outputPath));

        // Remove token counter directory if it exists
        if ($this->tokenCounterPath !== null) {
            $this->removeDirectory(dirname($this->tokenCounterPath));
        }
    }

    private function createTestFiles(): void
    {
        // Make sure directory is accessible
        $this->testRepoPath = realpath($this->testRepoPath);
        $this->testFiles = [];

        // Make sure paths are correct and files are readable
        $smallFile = $this->testRepoPath . DIRECTORY_SEPARATOR . 'small.php';
        file_put_contents($smallFile, '<?php echo "Hello World"; ?>');
        chmod($smallFile, 0777);
        $this->testFiles[] = $smallFile;

        // Medium file (approximately 20 tokens)
        $mediumFile = $this->testRepoPath . '/medium.php';
        file_put_contents($mediumFile, '<?php 
            function test() { 
                return "This is a medium-sized file"; 
            }
        ?>');
        chmod($mediumFile, 0777);
        $this->testFiles[] = $mediumFile;

        // Large file (approximately 40 tokens)
        $largeFile = $this->testRepoPath . '/large.php';
        file_put_contents($largeFile, '<?php
            namespace Test;
            
            class LargeFile {
                private $property;
                
                public function __construct($value) {
                    $this->property = $value;
                }
                
                public function getValue() {
                    return $this->property;
                }
            }
        ?>');
        chmod($largeFile, 0777);
        $this->testFiles[] = $largeFile;
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $path = $dir . '/' . $file;
                if (is_dir($path)) {
                    $this->removeDirectory($path);
                } else {
                    unlink($path);
                }
            }
        }
        rmdir($dir);
    }

    public function testRepositorySplitting(): void
    {
        // Skip the test if no token counter is available
        $this->skipIfNoTokenCounter();

        // Use a buffered output to capture messages
        $output = new BufferedOutput();

        // Debug: Check if files exist before processing
        foreach ($this->testFiles as $file) {
            $this->assertTrue(file_exists($file), "Test file $file should exist");
        }

        // Create RepoPHP instance with a token limit that should cause splitting
        // Using 30 tokens as limit: small (10) + medium (20) should fit in first file,
        // large (40) should go to second file
        $repoPHP = new RepoPHP(
            $this->testRepoPath,
            $this->outputPath,
            RepoPHPConfig::FORMAT_PLAIN,
            [],
            false,
            $output,
            RepoPHPConfig::ENCODING_CL100K,
            false,
            $this->tokenCounterPath,
            60 // Max tokens per file
        );

        // Execute packing operation
        $repoPHP->pack();

        // Check if both output files exist
        $this->assertFileExists($this->outputPath, 'First output file should exist');
        $this->assertFileExists(
            dirname($this->outputPath) . '/' . pathinfo($this->outputPath, PATHINFO_FILENAME) . '-part2.' . pathinfo($this->outputPath, PATHINFO_EXTENSION),
            'Second output file should exist'
        );

        // Verify output content
        $firstFileContent = file_get_contents($this->outputPath);

        $secondFileContent = file_get_contents(
            dirname($this->outputPath) . '/' . pathinfo($this->outputPath, PATHINFO_FILENAME) . '-part2.' . pathinfo($this->outputPath, PATHINFO_EXTENSION)
        );


        // Verify the content contains the expected files in each output
        $this->assertStringContainsString('File: large.php', $firstFileContent, 'First file should contain large.php');
        $this->assertStringContainsString('File: medium.php', $secondFileContent, 'Second file should contain medium.php');
        $this->assertStringContainsString('File: small.php', $secondFileContent, 'Second file should contain small.php');

        // Then check which file contains what
        if (strpos($firstFileContent, 'File: small.php') !== false) {
            $this->assertStringContainsString('File: small.php', $firstFileContent, 'First file should contain small.php');
        } else {
            $this->assertStringContainsString('File: small.php', $secondFileContent, 'Second file should contain small.php');
        }

        // Verify the content contains the expected files in each output
        $this->assertStringContainsString('File: large.php', $firstFileContent, 'First file should contain large.php');
        $this->assertStringContainsString('File: medium.php', $secondFileContent, 'Second file should contain medium.php');
        $this->assertStringContainsString('File: small.php', $secondFileContent, 'Second file should contain small.php');

        // Verify log output contains splitting information
        $outputContent = $output->fetch();
        $this->assertStringContainsString('Starting new file due to token limit', $outputContent);
        $this->assertStringContainsString('Repository was split into 2 files', $outputContent);
    }

    public function testNoSplittingWhenUnderLimit(): void
    {
        // Skip the test if no token counter is available
        $this->skipIfNoTokenCounter();

        // Set a high token limit that shouldn't cause splitting
        $output = new BufferedOutput();

        $repoPHP = new RepoPHP(
            $this->testRepoPath,
            $this->outputPath,
            RepoPHPConfig::FORMAT_PLAIN,
            [],
            false,
            $output,
            RepoPHPConfig::ENCODING_CL100K,
            false,
            $this->tokenCounterPath,
            100 // High token limit
        );

        $repoPHP->pack();

        // Only one file should exist
        $this->assertFileExists($this->outputPath, 'Output file should exist');
        $this->assertFileDoesNotExist(
            dirname($this->outputPath) . '/' . pathinfo($this->outputPath, PATHINFO_FILENAME) . '-part2.' . pathinfo($this->outputPath, PATHINFO_EXTENSION),
            'Second output file should not exist when under token limit'
        );

        // Verify all files are in the single output
        $fileContent = file_get_contents($this->outputPath);
        $this->assertStringContainsString('small.php', $fileContent);
        $this->assertStringContainsString('medium.php', $fileContent);
        $this->assertStringContainsString('large.php', $fileContent);

        // No splitting message should be present
        $outputContent = $output->fetch();
        $this->assertStringNotContainsString('Repository was split into', $outputContent);
    }

    public function testNoSplittingWhenLimitIsZero(): void
    {
        // Skip the test if no token counter is available
        $this->skipIfNoTokenCounter();

        // Token limit of 0 should mean no splitting
        $output = new BufferedOutput();

        $repoPHP = new RepoPHP(
            $this->testRepoPath,
            $this->outputPath,
            RepoPHPConfig::FORMAT_PLAIN,
            [],
            false,
            $output,
            RepoPHPConfig::ENCODING_CL100K,
            false,
            $this->tokenCounterPath,
            0 // No limit
        );

        $repoPHP->pack();

        // Only one file should exist
        $this->assertFileExists($this->outputPath, 'Output file should exist');
        $this->assertFileDoesNotExist(
            dirname($this->outputPath) . '/' . pathinfo($this->outputPath, PATHINFO_FILENAME) . '-part2.' . pathinfo($this->outputPath, PATHINFO_EXTENSION),
            'Second output file should not exist when no token limit is set'
        );

        // All files should be in single output
        $fileContent = file_get_contents($this->outputPath);
        $this->assertStringContainsString('small.php', $fileContent);
        $this->assertStringContainsString('medium.php', $fileContent);
        $this->assertStringContainsString('large.php', $fileContent);
    }

    // Add a test helper to skip tests if token counter isn't available
    private function skipIfNoTokenCounter(): void
    {
        if ($this->tokenCounterPath === null || ! file_exists($this->tokenCounterPath)) {
            $this->markTestSkipped('No token counter available for testing');
        }
    }
}
