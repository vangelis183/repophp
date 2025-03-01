<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Vangelis\RepoPHP\Config\RepoPHPConfig;
use Vangelis\RepoPHP\RepoPHP;

class RepoPHPIncrementalTest extends TestCase
{
    private string $testRepoPath;
    private string $outputPath;
    private string $diffOutputPath;
    private ?string $tokenCounterPath = null;

    protected function setUp(): void
    {
        // Create a temporary test repository
        $this->testRepoPath = sys_get_temp_dir() . '/repophp_inc_test_' . uniqid();
        mkdir($this->testRepoPath, 0777, true);

        // Create output directory
        $outputDir = sys_get_temp_dir() . '/repophp_inc_output_' . uniqid();
        mkdir($outputDir, 0755, true);
        $this->outputPath = $outputDir . '/output.txt';
        $this->diffOutputPath = $outputDir . '/output_diff_' . date('Y-m-d_His') . '.txt';

        // Setup token counter in a temporary location
        $this->setupTokenCounter();

        // Initialize git repository
        exec('git -C ' . escapeshellarg($this->testRepoPath) . ' init');
        exec('git -C ' . escapeshellarg($this->testRepoPath) . ' config user.name "Test User"');
        exec('git -C ' . escapeshellarg($this->testRepoPath) . ' config user.email "test@example.com"');

        // Create initial files
        file_put_contents($this->testRepoPath . '/file1.php', '<?php echo "Initial file 1"; ?>');
        file_put_contents($this->testRepoPath . '/file2.php', '<?php echo "Initial file 2"; ?>');

        // Add and commit initial files
        exec('git -C ' . escapeshellarg($this->testRepoPath) . ' add .');
        exec('git -C ' . escapeshellarg($this->testRepoPath) . ' commit -m "Initial commit"');
    }

    protected function tearDown(): void
    {
        // Clean up test files and directories
        $this->removeDirectory($this->testRepoPath);
        $this->removeDirectory(dirname($this->outputPath));

        if ($this->tokenCounterPath !== null) {
            $this->removeDirectory(dirname($this->tokenCounterPath));
        }
    }

    private function setupTokenCounter(): void
    {
        // Same implementation as in RepoPHPSplittingTest
        $tempDir = sys_get_temp_dir() . '/repophp_inc_token_counter_' . uniqid();
        mkdir($tempDir, 0777, true);

        $tempTokenCounter = $tempDir . '/mock-token-counter';
        file_put_contents($tempTokenCounter, '#!/bin/bash' . PHP_EOL . 'echo "10"');
        chmod($tempTokenCounter, 0777);

        $this->tokenCounterPath = $tempTokenCounter;
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

    public function testIncrementalPacking(): void
    {
        // Skip if we couldn't set up a token counter
        if ($this->tokenCounterPath === null) {
            $this->markTestSkipped('No token counter available for testing');
        }

        // Create initial pack
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
            $this->tokenCounterPath
        );
        $repoPHP->pack();

        // Verify initial pack contains both files
        $this->assertFileExists($this->outputPath);
        $initialContent = file_get_contents($this->outputPath);
        $this->assertStringContainsString('file1.php', $initialContent);
        $this->assertStringContainsString('file2.php', $initialContent);

        // Make changes to the repository
        file_put_contents($this->testRepoPath . '/file1.php', '<?php echo "Modified file 1"; ?>');
        file_put_contents($this->testRepoPath . '/file3.php', '<?php echo "New file 3"; ?>');

        // Add and commit changes
        exec('git -C ' . escapeshellarg($this->testRepoPath) . ' add .');
        exec('git -C ' . escapeshellarg($this->testRepoPath) . ' commit -m "Update files"');

        // Create incremental pack
        $diffOutput = new BufferedOutput();
        $outputDir = dirname($this->outputPath);

        // Use the actual output path rather than a pre-determined one
        $incrementalOutputPath = $outputDir . '/incremental_output.txt';

        $repoPHPIncremental = new RepoPHP(
            $this->testRepoPath,
            $incrementalOutputPath,
            RepoPHPConfig::FORMAT_PLAIN,
            [],
            false,
            $diffOutput,
            RepoPHPConfig::ENCODING_CL100K,
            false,
            $this->tokenCounterPath,
            0, // No token limit
            true, // Enable incremental mode
            $this->outputPath // Use initial pack as base file
        );
        $repoPHPIncremental->pack();

        // Find the actual diff file that was created
        $diffFiles = glob($outputDir . '/*_diff_*');
        $this->assertNotEmpty($diffFiles, 'No diff file was created');
        $diffFilePath = reset($diffFiles);

        // Verify the incremental pack contains only the changed and new files
        $diffContent = file_get_contents($diffFilePath);
        $this->assertStringContainsString('file1.php', $diffContent);
        $this->assertStringContainsString('file3.php', $diffContent);
        $this->assertStringNotContainsString('file2.php', $diffContent);

        // Verify the console output mentions it's an incremental pack
        $outputText = $diffOutput->fetch();
        $this->assertStringContainsString('Incremental Pack Information', $outputText);
        $this->assertStringContainsString('Base File:', $outputText);
    }
}
