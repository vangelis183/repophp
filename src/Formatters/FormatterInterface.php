<?php

namespace Vangelis\RepoPHP\Formatters;

interface FormatterInterface
{
    public function initialize(): void;

    public function getHeader(): string;

    public function getFooter(): string;

    public function formatFile(string $path, string $content): string;

    public function getSeparator(): string;

    public function validateContent(string $content): bool;
}

