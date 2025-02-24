<?php

namespace Vangelis\RepoPHP\Formatters;

use Symfony\Component\Console\Output\OutputInterface;

class JsonFormatter extends BaseFormatter
{
    private array $files = [];
    private ?array $gitInfo;
    private bool $headerWritten = false;

    public function __construct(?OutputInterface $output = null, ?array $gitInfo = null)
    {
        parent::__construct($output);
        $this->gitInfo = $gitInfo;
    }

    public function getHeader(): string
    {
        if (!$this->headerWritten) {
            $this->headerWritten = true;
            return "{\n  \"files\": [";
        }
        return '';
    }

    public function formatFile(string $path, string $content): string
    {
        if ($this->output) {
            $this->output->writeln("Adding to JSON: {$path}");
        }

        $fileData = [
            'path' => $this->formatPath($path),
            'content' => base64_encode($content)
        ];

        $this->files[] = $fileData;
        $fileOutput = json_encode($fileData, JSON_UNESCAPED_SLASHES);

        return (!empty($this->files) && count($this->files) > 1 ? ',' : '') . "\n    " . $fileOutput;
    }

    public function getFooter(): string
    {
        $totalSize = 0;
        foreach ($this->files as $file) {
            $totalSize += strlen(base64_decode($file['content']));
        }

        $stats = [
            'file_count' => count($this->files),
            'total_size' => $totalSize
        ];

        if ($this->gitInfo) {
            $stats['git'] = $this->gitInfo;
        }

        $statsJson = json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $footer = "\n  ],\n  \"stats\": " . $statsJson . "\n}";

        // Clear the state only after generating all content
        $this->files = [];
        $this->headerWritten = false;

        return $footer;
    }

    public function getSeparator(): string
    {
        return '';
    }
}
