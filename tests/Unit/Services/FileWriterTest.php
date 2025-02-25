<?php

namespace Vangelis\RepoPHP\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Vangelis\RepoPHP\Config\RepoPHPConfig;
use Vangelis\RepoPHP\Factory\FormatterFactory;
use Vangelis\RepoPHP\Services\FileWriter;

class FileWriterTest extends TestCase
{
    private string $tempDir;
    private string $outputPath;
    private string $repoPath;
    private FormatterFactory $formatterFactory;
    private RepoPHPConfig $config;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/repophp-filewriter-' . uniqid();
        mkdir($this->tempDir, 0777, true);

        $this->outputPath = $this->tempDir . '/output.txt';
        $this->repoPath = $this->tempDir . '/repo';
        mkdir($this->repoPath);

        file_put_contents($this->repoPath . '/test.php', '<?php echo "Hello World"; ?>');

        $this->formatterFactory = new FormatterFactory();
        $this->config = new RepoPHPConfig(RepoPHPConfig::FORMAT_PLAIN);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->repoPath . '/test.php')) {
            unlink($this->repoPath . '/test.php');
        }

        if (file_exists($this->outputPath)) {
            unlink($this->outputPath);
        }

        if (is_dir($this->repoPath)) {
            rmdir($this->repoPath);
        }

        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testWriteHeader()
    {
        $writer = new FileWriter(
            $this->formatterFactory,
            $this->config,
            null,
            $this->outputPath,
            $this->repoPath
        );

        $handle = fopen($this->outputPath, 'wb');
        $writer->writeHeader($handle);
        fclose($handle);

        $content = file_get_contents($this->outputPath);
        $this->assertStringContainsString('Repository Export', $content); // Angepasst an die tatsächliche Ausgabe
        $this->assertStringContainsString('================================================================', $content);
    }

    public function testWriteContent()
    {
        $writer = new FileWriter(
            $this->formatterFactory,
            $this->config,
            null,
            $this->outputPath,
            $this->repoPath
        );

        $handle = fopen($this->outputPath, 'wb');
        $writer->writeContent($handle, 'test.php', $this->repoPath . '/test.php');
        fclose($handle);

        $content = file_get_contents($this->outputPath);
        $this->assertStringContainsString('test.php', $content);
        $this->assertStringContainsString('Hello World', $content);
    }

    public function testWriteFooter()
    {
        $writer = new FileWriter(
            $this->formatterFactory,
            $this->config,
            null,
            $this->outputPath,
            $this->repoPath
        );

        $handle = fopen($this->outputPath, 'wb');
        $writer->writeFooter($handle);
        fclose($handle);

        $content = file_get_contents($this->outputPath);
        $this->assertStringContainsString('End of Repository Export', $content); // Angepasst an die tatsächliche Ausgabe
    }
}
