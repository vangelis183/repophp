<?php

namespace Vangelis\RepoPHP\Analyzers;

use Vangelis\RepoPHP\Exceptions\TokenCounterException;

class TokenCounter
{
    private string $executablePath;
    private array $mimeTypeCache = [];

    public function __construct(string $executablePath)
    {
        if (! file_exists($executablePath)) {
            throw new TokenCounterException("Token counter executable not found at: $executablePath");
        }
        $this->executablePath = $executablePath;
    }

    public function countTokens(string $filePath, string $encoding): int
    {
        if ($this->isBinaryFile($filePath)) {
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
        if (! file_exists($filePath)) {
            return false;
        }

        if (isset($this->mimeTypeCache[$filePath])) {
            return $this->mimeTypeCache[$filePath];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        $isBinary = ! str_starts_with($mimeType, 'text/')
            && ! in_array($mimeType, [
                'application/x-httpd-php',
                'application/json',
                'application/xml',
                'application/javascript',
                'application/x-javascript',
                'application/ecmascript',
                'application/x-yaml',
                'application/x-perl',
                'application/x-sh',
                'application/x-ruby',
                'application/x-python',
            ], true);

        $this->mimeTypeCache[$filePath] = $isBinary;

        return $isBinary;
    }
}
