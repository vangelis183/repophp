<?php

namespace Vangelis\RepoPHP\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Vangelis\RepoPHP\Services\FileCollector;

class FileCollectorTest extends TestCase
{
    private string $tempDir;
    private string $repositoryRoot;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/repophp-filecollector-' . uniqid();
        $this->repositoryRoot = $this->tempDir . '/repo';

        mkdir($this->repositoryRoot, 0777, true);
        file_put_contents($this->repositoryRoot . '/test.php', '<?php echo "Hello World"; ?>');
        file_put_contents($this->repositoryRoot . '/test.txt', 'Plain text file');
        file_put_contents($this->repositoryRoot . '/.gitignore', 'ignored.php');
        file_put_contents($this->repositoryRoot . '/ignored.php', '<?php echo "Ignored file"; ?>');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->repositoryRoot . '/test.php')) {
            unlink($this->repositoryRoot . '/test.php');
        }

        if (file_exists($this->repositoryRoot . '/test.txt')) {
            unlink($this->repositoryRoot . '/test.txt');
        }

        if (file_exists($this->repositoryRoot . '/.gitignore')) {
            unlink($this->repositoryRoot . '/.gitignore');
        }

        if (file_exists($this->repositoryRoot . '/ignored.php')) {
            unlink($this->repositoryRoot . '/ignored.php');
        }

        if (is_dir($this->repositoryRoot)) {
            rmdir($this->repositoryRoot);
        }

        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testCollectFiles()
    {
        $finder = new Finder();
        $fileCollector = new FileCollector($finder, [], false, $this->repositoryRoot);

        $files = iterator_to_array($fileCollector->collectFiles());

        $this->assertCount(4, $files); // Alle 4 Dateien werden erfasst
        $this->assertContainsFilePath($files, '/test.php');
        $this->assertContainsFilePath($files, '/test.txt');
        $this->assertContainsFilePath($files, '/.gitignore');
        $this->assertContainsFilePath($files, '/ignored.php');
    }

    public function testCollectFilesWithGitignore(): void
    {
        $finder = new Finder();
        $finder->ignoreDotFiles(false);
        $fileCollector = new FileCollector($finder, [], true, $this->repositoryRoot);

        $files = iterator_to_array($fileCollector->collectFiles());

        $this->assertCount(4, $files); // All files are included since .gitignore isn't implemented yet
        $this->assertContainsFilePath($files, '/test.php');
        $this->assertContainsFilePath($files, '/test.txt');
        $this->assertContainsFilePath($files, '/.gitignore');
        $this->assertContainsFilePath($files, '/ignored.php');
    }

    public function testCollectFilesWithExcludePatterns()
    {
        $finder = new Finder();
        $fileCollector = new FileCollector($finder, ['*.txt'], false, $this->repositoryRoot);

        $files = iterator_to_array($fileCollector->collectFiles());

        $this->assertCountLessThan(4, $files); // Mindestens eine Datei sollte ausgeschlossen sein
        $this->assertContainsFilePath($files, '/test.php');
        $this->assertNotContainsFilePath($files, '/test.txt');
    }

    private function assertContainsFilePath(array $files, string $relativePath)
    {
        $expected = realpath($this->repositoryRoot . $relativePath);
        $found = false;

        foreach ($files as $file) {
            if (realpath($file) === $expected) {
                $found = true;

                break;
            }
        }

        $this->assertTrue($found, "File $expected not found in collected files");
    }

    private function assertNotContainsFilePath(array $files, string $relativePath)
    {
        $unexpected = realpath($this->repositoryRoot . $relativePath);

        foreach ($files as $file) {
            $this->assertNotEquals(
                $unexpected,
                realpath($file),
                "File $unexpected should not be in collected files"
            );
        }
    }

    private function assertCountLessThan(int $expectedCount, array $collection)
    {
        $this->assertLessThan($expectedCount, count($collection));
    }
}
