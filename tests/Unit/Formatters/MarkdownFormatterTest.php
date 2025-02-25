<?php

namespace Vangelis\RepoPHP\Tests\Unit\Formatters;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Vangelis\RepoPHP\Formatters\MarkdownFormatter;

class MarkdownFormatterTest extends TestCase
{
    private MarkdownFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new MarkdownFormatter();
    }

    public function testGetHeader(): void
    {
        $header = $this->formatter->getHeader();

        $this->assertStringContainsString('# Repository Export', $header);
        $this->assertStringContainsString('Generated:', $header);
        $this->assertStringContainsString(date('Y-m-d'), $header);
        $this->assertStringContainsString('---', $header);
    }

    public function testGetFooter(): void
    {
        $footer = $this->formatter->getFooter();

        $this->assertStringContainsString('---', $footer);
        $this->assertStringContainsString('*End of Repository Export*', $footer);
    }

    #[DataProvider('fileProvider')]
    public function testFormatFile(string $path, string $content, string $expectedLanguage): void
    {
        $formatted = $this->formatter->formatFile($path, $content);

        $this->assertStringContainsString("### File: {$path}", $formatted);
        $this->assertStringContainsString("```{$expectedLanguage}", $formatted);
        $this->assertStringContainsString($content, $formatted);
        $this->assertStringContainsString('```', $formatted);
    }

    public static function fileProvider(): array
    {
        return [
            'PHP file' => ['src/test.php', '<?php echo "test"; ?>', 'php'],
            'JavaScript file' => ['assets/script.js', 'console.log("test");', 'javascript'],
            'TypeScript file' => ['components/App.tsx', 'export const App = () => {};', 'tsx'],
            'CSS file' => ['styles/main.css', 'body { color: black; }', 'css'],
            'YAML file' => ['config.yml', 'key: value', 'yaml'],
            'Unknown extension' => ['data.xyz', 'random content', 'plaintext'],
        ];
    }

    public function testCompleteOutput(): void
    {
        $output = $this->formatter->getHeader() .
                 $this->formatter->formatFile('test.php', '<?php echo "test"; ?>') .
                 $this->formatter->getFooter();

        $this->assertStringContainsString('# Repository Export', $output);
        $this->assertStringContainsString('### File: test.php', $output);
        $this->assertStringContainsString('```php', $output);
        $this->assertStringContainsString('<?php echo "test"; ?>', $output);
        $this->assertStringContainsString('*End of Repository Export*', $output);
    }
}
