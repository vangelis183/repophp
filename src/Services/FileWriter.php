<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Services;

use Vangelis\RepoPHP\Config\RepoPHPConfig;
use Vangelis\RepoPHP\Exceptions\FileWriteException;
use Vangelis\RepoPHP\Factory\FormatterFactory;

class FileWriter
{
    public function __construct(
        private FormatterFactory $formatterFactory,
        private RepoPHPConfig $config,
    ) {
    }

    /**
     * @throws \Vangelis\RepoPHP\Exceptions\UnsupportedFormatException
     * @throws \Vangelis\RepoPHP\Exceptions\FileWriteException
     */
    public function writeContent($outputHandle, string $relativePath, string $filePath): void
    {
        if (! is_readable($filePath)) {
            fwrite(STDERR, "Warning: Cannot read file '$filePath', skipping.\n");
            return;
        }

        $content = $this->readFile($filePath);
        $formatter = $this->formatterFactory->createFormatter($this->config->getFormat());

        $formattedContent = $formatter->formatFile($relativePath, $content);
        fwrite($outputHandle, $formattedContent);
        fwrite($outputHandle, $formatter->getSeparator());
    }

    /**
     * @throws \Vangelis\RepoPHP\Exceptions\UnsupportedFormatException
     */
    public function writeHeader($outputHandle): void
    {
        $formatter = $this->formatterFactory->createFormatter($this->config->getFormat());
        $header = $formatter->getHeader();
        fwrite($outputHandle, $header . $formatter->getSeparator());
    }

    /**
     * @throws \Vangelis\RepoPHP\Exceptions\UnsupportedFormatException
     */
    public function writeFooter($outputHandle): void
    {
        $formatter = $this->formatterFactory->createFormatter($this->config->getFormat());
        $footer = $formatter->getFooter();
        fwrite($outputHandle, $footer);
    }

    /**
     * @throws \Vangelis\RepoPHP\Exceptions\FileWriteException
     */
    private function readFile(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new FileWriteException("Failed to read file: $filePath");
        }
        return $content;
    }
}
