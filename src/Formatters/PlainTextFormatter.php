<?php

namespace Vangelis\RepoPHP\Formatters;

class PlainTextFormatter extends BaseFormatter
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getHeader(): string
    {
        return $this->getDefaultHeader();
    }

    public function getFooter(): string
    {
        return "\n================================================================\nEnd of Repository Export\n";
    }

    public function formatFile(string $path, string $content): string
    {
        return <<<EOT
================
File: {$this->formatPath($path)}
================
{$content}
EOT;
    }
}
