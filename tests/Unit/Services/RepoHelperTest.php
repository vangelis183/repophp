<?php

namespace Vangelis\RepoPHP\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Vangelis\RepoPHP\Services\RepoHelper;

class RepoHelperTest extends TestCase
{
    private string $tempDir;
    private RepoHelper $helper;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/repo_helper_test_' . uniqid();
        mkdir($this->tempDir);

        // Initialize Git repository
        exec('git -C ' . escapeshellarg($this->tempDir) . ' init');
        exec('git -C ' . escapeshellarg($this->tempDir) . ' config user.email "test@example.com"');
        exec('git -C ' . escapeshellarg($this->tempDir) . ' config user.name "Test User"');

        // Create and commit a test file
        file_put_contents($this->tempDir . '/test.txt', 'test content');
        exec('git -C ' . escapeshellarg($this->tempDir) . ' add test.txt');
        exec('git -C ' . escapeshellarg($this->tempDir) . ' commit -m "Test commit"');

        // Add a remote
        exec('git -C ' . escapeshellarg($this->tempDir) . ' remote add origin https://github.com/test/repo.git');

        $this->helper = new RepoHelper($this->tempDir, new BufferedOutput());
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testGetGitInfo(): void
    {
        $info = $this->helper->getGitInfo();

        // Accept either 'main' or 'master' as valid default branch names
        $this->assertContains($info['branch'], ['main', 'master']);
        $this->assertArrayHasKey('commit', $info);
        $this->assertArrayHasKey('hash', $info['commit']);
        $this->assertEquals('Test User', $info['commit']['author']);
        $this->assertEquals('Test commit', $info['commit']['message']);
        $this->assertArrayHasKey('origin', $info['remotes']);
        $this->assertEquals('https://github.com/test/repo.git', $info['remotes']['origin']['fetch']);
    }

    public function testFormatRepositoryInfoText(): void
    {
        $info = $this->helper->formatRepositoryInfo('text');

        $expectedFormat = "\nRepository Information:\n---------------------\n" .
            "Branch: %s\n" .
            "Commit: %s\n" .
            "Author: Test User\n" .
            "Message: Test commit\n" .
            "Remotes:\n" .
            "  - origin: https://github.com/test/repo.git\n";

        $this->assertStringMatchesFormat($expectedFormat, $info);
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
}
