<?php

namespace Vangelis\RepoPHP\Formatters;

interface FormatterInterface
{
    /**
     * Get the header content for the output file
     */
    public function getHeader(): string;

    /**
     * Get the footer content for the output file
     */
    public function getFooter(): string;

    /**
     * Format a single file's content
     */
    public function formatFile(string $path, string $content): string;

    /**
     * Get the separator between files
     */
    public function getSeparator(): string;
}
