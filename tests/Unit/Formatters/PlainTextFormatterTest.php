<?php

namespace Vangelis\RepoPHP\Tests\Unit\Formatters;

use PHPUnit\Framework\TestCase;
use Vangelis\RepoPHP\Formatters\PlainTextFormatter;

class PlainTextFormatterTest extends TestCase
{
    private PlainTextFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new PlainTextFormatter();
    }

    public function testGetHeader(): void
    {
        $header = $this->formatter->getHeader();

        $this->assertStringContainsString('Repository Export', $header);
        $this->assertStringContainsString('Generated:', $header);
        $this->assertStringContainsString(date('Y-m-d'), $header);
    }

    public function testGetFooter(): void
    {
        $footer = $this->formatter->getFooter();

        $this->assertStringContainsString('================================================================', $footer);
        $this->assertStringContainsString('End of Repository Export', $footer);
    }

    public function testFormatFile(): void
    {
        $path = 'src/test.php';
        $content = '<?php echo "Hello World"; ?>';

        $formatted = $this->formatter->formatFile($path, $content);

        $this->assertStringContainsString('================', $formatted);
        $this->assertStringContainsString('File: src/test.php', $formatted);
        $this->assertStringContainsString('<?php echo "Hello World"; ?>', $formatted);
    }

    public function testFormatFileWithNestedPath(): void
    {
        $path = 'src/deeply/nested/test.php';
        $content = '<?php echo "Test"; ?>';

        $formatted = $this->formatter->formatFile($path, $content);

        $this->assertStringContainsString('File: src/deeply/nested/test.php', $formatted);
        $this->assertStringContainsString($content, $formatted);
    }

    public function testCompleteOutput(): void
    {
        $output = $this->formatter->getHeader() .
                 $this->formatter->formatFile('test.php', '<?php echo "test"; ?>') .
                 $this->formatter->getFooter();

        $this->assertStringContainsString('Repository Export', $output);
        $this->assertStringContainsString('File: test.php', $output);
        $this->assertStringContainsString('<?php echo "test"; ?>', $output);
        $this->assertStringContainsString('End of Repository Export', $output);
    }
}
