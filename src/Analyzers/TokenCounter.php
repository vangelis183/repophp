<?php

namespace Vangelis\RepoPHP\Analyzers;

use Vangelis\RepoPHP\Exceptions\TokenCounterException;
use Vangelis\RepoPHP\Services\BinaryFileDetector;

class TokenCounter
{
    private string $executablePath;
    private readonly BinaryFileDetector $binaryFileDetector;

    public function __construct(string $executablePath)
    {
        if (! file_exists($executablePath)) {
            throw new TokenCounterException("Token counter executable not found at: $executablePath");
        }
        $this->executablePath = $executablePath;
        $this->binaryFileDetector = new BinaryFileDetector();
    }

    public function countTokens(string $filePath, string $encoding): int
    {
        if ($this->isBinaryFile($filePath)) {
            return 0;
        }
        // Skip non-text files or special files like .gitignore
        if ($this->isSpecialFile($filePath) || ! $this->isTextFile($filePath)) {
            return 0;
        }

        $command = sprintf(
            '%s -encoding %s -file %s',
            escapeshellcmd($this->executablePath),
            escapeshellarg($encoding),
            escapeshellarg($filePath)
        );

        $process = proc_open($command, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (! is_resource($process)) {
            throw new TokenCounterException("Failed to execute token counter for file: $filePath");
        }

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        if ($returnCode !== 0) {
            throw new TokenCounterException("Token counter command failed with code $returnCode for file: $filePath");
        }

        return (int) trim($output);
    }

    private function isBinaryFile(string $filePath): bool
    {
        return $this->binaryFileDetector->isBinary($filePath);
    }

    private function isTextFile(string $filePath): bool
    {
        $finfo = finfo_open(FILEINFO_MIME);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return str_contains($mimeType, 'text/') || str_contains($mimeType, '/xml');
    }

    private function isSpecialFile(string $filePath): bool
    {
        $specialFiles = [
            '.gitignore',
            '.gitattributes',
            '.env',
            '.editorconfig',
        ];

        return in_array(basename($filePath), $specialFiles, true);
    }
}
