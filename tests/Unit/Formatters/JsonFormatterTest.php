<?php

namespace Vangelis\RepoPHP\Tests\Unit\Formatters;

use PHPUnit\Framework\TestCase;
use Vangelis\RepoPHP\Formatters\JsonFormatter;

class JsonFormatterTest extends TestCase
{
    private JsonFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new JsonFormatter();
    }

    public function testFormatFileReturnsCorrectJsonStructure(): void
    {
        $path = 'src/test.php';
        $content = '<?php echo "test"; ?>';
        $formatted = $this->formatter->formatFile($path, $content);

        $expected = [
            'path' => 'src/test.php',
            'content' => base64_encode($content),
        ];

        $this->assertStringContainsString(json_encode($expected, JSON_UNESCAPED_SLASHES), $formatted);
    }

    public function testFormatFileHandlesWindowsPaths(): void
    {
        $path = 'src\\test\\file.php';
        $content = '<?php echo "test"; ?>';
        $formatted = $this->formatter->formatFile($path, $content);

        $this->assertStringContainsString('src/test/file.php', $formatted);
        $this->assertStringNotContainsString('src\\test\\file.php', $formatted);
    }

    public function testFormatFileAddsCommaForSubsequentFiles(): void
    {
        $firstFile = $this->formatter->formatFile('first.php', 'first content');
        $secondFile = $this->formatter->formatFile('second.php', 'second content');

        $this->assertStringNotContainsString(',{', $firstFile);
        $this->assertStringStartsWith(",\n    {", $secondFile);
    }

    public function testCompleteJsonOutputIsValid(): void
    {
        $output = $this->formatter->getHeader();
        $output .= $this->formatter->formatFile('test1.php', 'content1');
        $output .= $this->formatter->formatFile('test2.php', 'content2');
        $output .= $this->formatter->getFooter();

        $decodedOutput = json_decode($output, true);

        $this->assertIsArray($decodedOutput);
        $this->assertArrayHasKey('files', $decodedOutput);
        $this->assertCount(2, $decodedOutput['files']);
        $this->assertArrayHasKey('stats', $decodedOutput);
        $this->assertEquals(2, $decodedOutput['stats']['file_count']);
    }
}
