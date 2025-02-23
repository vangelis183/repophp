<?php

namespace Vangelis\RepoPHP\Formatters;

use Symfony\Component\Console\Output\OutputInterface;

class JsonFormatter extends BaseFormatter
{
    private array $files = [];

    private ?OutputInterface $output;

    public function __construct(?OutputInterface $output = null)
    {
        $this->output = $output;
    }


    public function getHeader(): string
    {
        return '';  // JSON structure will be written in getFooter
    }

    public function getFooter(): string
    {
        $output = [
            'metadata' => [
                'generated_at' => $this->getDateTime(),
                'file_count' => count($this->files),
            ],
            'files' => $this->files,
        ];

        // Reset files array for next use
        $this->files = [];

        return json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function formatFile(string $path, string $content): string
    {
        if ($this->output) {
            $this->output->writeln(sprintf(
                '<comment>Adding to JSON: %s (size: %d bytes)</comment>',
                $this->formatPath($path),
                strlen($content)
            ));
        }

        $this->files[] = [
            'path' => $this->formatPath($path),
            'size' => strlen($content),
            'extension' => pathinfo($path, PATHINFO_EXTENSION),
            'content' => $content,
        ];

        return ''; // Actual content will be written in getFooter
    }

    public function getSeparator(): string
    {
        return ''; // Not needed for JSON format
    }
}
