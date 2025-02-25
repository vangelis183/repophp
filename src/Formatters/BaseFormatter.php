<?php

namespace Vangelis\RepoPHP\Formatters;

use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseFormatter implements FormatterInterface
{
    public function __construct(
        protected ?OutputInterface $output = null
    ) {
    }

    public function initialize(): void
    {
        // Override in concrete formatters if needed
    }

    public function validateContent(string $content): bool
    {
        return true; // Override in concrete formatters if needed
    }

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
