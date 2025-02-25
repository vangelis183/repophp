<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Factory;

use Symfony\Component\Console\Output\OutputInterface;
use Vangelis\RepoPHP\Config\RepoPHPConfig;
use Vangelis\RepoPHP\Formatters\FormatterInterface;
use Vangelis\RepoPHP\Formatters\JsonFormatter;
use Vangelis\RepoPHP\Formatters\MarkdownFormatter;
use Vangelis\RepoPHP\Formatters\PlainTextFormatter;
use Vangelis\RepoPHP\Formatters\XmlFormatter;
use Vangelis\RepoPHP\Exceptions\UnsupportedFormatException;

class FormatterFactory
{
    private const FORMATTERS = [
        RepoPHPConfig::FORMAT_PLAIN => PlainTextFormatter::class,
        RepoPHPConfig::FORMAT_MARKDOWN => MarkdownFormatter::class,
        RepoPHPConfig::FORMAT_JSON => JsonFormatter::class,
        RepoPHPConfig::FORMAT_XML => XmlFormatter::class,
    ];

    /**
     * @throws \Vangelis\RepoPHP\Exceptions\UnsupportedFormatException
     */
    public function createFormatter(string $format): FormatterInterface
    {
        if (! isset(self::FORMATTERS[$format])) {
            throw new UnsupportedFormatException("Unsupported format: {$format}");
        }

        $formatterClass = self::FORMATTERS[$format];

        return new $formatterClass();
    }
}
