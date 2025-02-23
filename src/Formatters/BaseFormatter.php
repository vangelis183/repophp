<?php

namespace Vangelis\RepoPHP\Formatters;

abstract class BaseFormatter implements FormatterInterface
{
    protected function getDateTime(): string
    {
        return date('Y-m-d H:i:s');
    }

    protected function getDefaultHeader(): string
    {
        return <<<EOT
================================================================
Repository Export
Generated: {$this->getDateTime()}
================================================================
EOT;
    }

    protected function formatPath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    public function getSeparator(): string
    {
        return "\n\n";
    }
}
