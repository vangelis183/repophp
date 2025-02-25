<?php

namespace Vangelis\RepoPHP\Tests\Unit\Formatters;

use PHPUnit\Framework\TestCase;
use Vangelis\RepoPHP\Formatters\XmlFormatter;

class XmlFormatterTest extends TestCase
{
    private XmlFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new XmlFormatter();
    }

    public function testGetHeader(): void
    {
        $header = $this->formatter->getHeader();

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $header);
        $this->assertStringContainsString('<repository>', $header);
        $this->assertStringContainsString('<metadata>', $header);
        $this->assertStringContainsString('<title>Repository Export</title>', $header);
        $this->assertStringContainsString('<generated_at>', $header);

        // Validate XML structure
        $xml = simplexml_load_string($header . '</repository>');
        $this->assertNotFalse($xml);
    }

    public function testGetFooter(): void
    {
        $this->assertEquals('</repository>', $this->formatter->getFooter());
    }

    public function testFormatFile(): void
    {
        $path = 'src/test.php';
        $content = '<?php echo "test"; ?>';

        $formatted = $this->formatter->formatFile($path, $content);

        // Test XML structure
        $xml = simplexml_load_string('<repository>' . $formatted . '</repository>');
        $this->assertNotFalse($xml);

        // Test content
        $this->assertStringContainsString('<file>', $formatted);
        $this->assertStringContainsString('<path>src/test.php</path>', $formatted);
        $this->assertStringContainsString('<![CDATA[<?php echo "test"; ?>]]>', $formatted);
    }

    public function testFormatFileWithSpecialCharacters(): void
    {
        $path = 'test & file.php';
        $content = '<script>alert("XSS")</script>';

        $formatted = $this->formatter->formatFile($path, $content);

        // Test XML structure
        $xml = simplexml_load_string('<repository>' . $formatted . '</repository>');
        $this->assertNotFalse($xml);

        // Test escaping
        $this->assertStringContainsString('test &amp; file.php', $formatted);
        $this->assertStringContainsString('<![CDATA[<script>alert("XSS")</script>]]>', $formatted);
    }

    public function testCompleteXmlDocument(): void
    {
        $xml = $this->formatter->getHeader() .
               $this->formatter->formatFile('test.php', '<?php echo "test"; ?>') .
               $this->formatter->getFooter();

        // Validate complete XML document
        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc);
        $this->assertInstanceOf(\SimpleXMLElement::class, $doc);
    }
}
