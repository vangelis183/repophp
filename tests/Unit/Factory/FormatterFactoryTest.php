<?php

namespace Vangelis\RepoPHP\Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Vangelis\RepoPHP\Config\RepoPHPConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use Vangelis\RepoPHP\Exceptions\UnsupportedFormatException;
use Vangelis\RepoPHP\Factory\FormatterFactory;
use Vangelis\RepoPHP\Formatters\JsonFormatter;
use Vangelis\RepoPHP\Formatters\MarkdownFormatter;
use Vangelis\RepoPHP\Formatters\PlainTextFormatter;
use Vangelis\RepoPHP\Formatters\XmlFormatter;

class FormatterFactoryTest extends TestCase
{
    private FormatterFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new FormatterFactory();
    }

    /**
     * @throws \Vangelis\RepoPHP\Exceptions\UnsupportedFormatException
     */
    #[DataProvider('formatterProvider')]
    public function testCreatesCorrectFormatterInstance(string $format, string $expectedClass): void
    {
        $formatter = $this->factory->createFormatter($format);

        $this->assertInstanceOf($expectedClass, $formatter);
    }

    public static function formatterProvider(): array
    {
        return [
            'plain text formatter' => [RepoPHPConfig::FORMAT_PLAIN, PlainTextFormatter::class],
            'markdown formatter' => [RepoPHPConfig::FORMAT_MARKDOWN, MarkdownFormatter::class],
            'json formatter' => [RepoPHPConfig::FORMAT_JSON, JsonFormatter::class],
            'xml formatter' => [RepoPHPConfig::FORMAT_XML, XmlFormatter::class],
        ];
    }

    public function testThrowsExceptionForUnsupportedFormat(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessage('Unsupported format: invalid');

        $this->factory->createFormatter('invalid');
    }
}
