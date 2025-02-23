<?php

namespace Vangelis\RepoPHP\Formatters;

use Symfony\Component\Console\Output\OutputInterface;

class PlainTextFormatter extends BaseFormatter
{
    private ?OutputInterface $output;

    public function __construct(?OutputInterface $output = null)
    {
        $this->output = $output;
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
        if ($this->output) {
            $this->output->writeln(sprintf(
                '<comment>Adding to Plaintext: %s (size: %d bytes)</comment>',
                $this->formatPath($path),
                strlen($content)
            ));
        }

        return <<<EOT
================
File: {$this->formatPath($path)}
================
{$content}
EOT;
    }
}
