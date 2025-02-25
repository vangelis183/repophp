<?php

namespace Vangelis\RepoPHP\Services;

class GitignoreReader
{
    private array $patterns = [];

    public function __construct(string $gitignorePath)
    {
        if (file_exists($gitignorePath)) {
            $this->patterns = array_filter(
                explode("\n", file_get_contents($gitignorePath)),
                fn ($pattern) => ! empty($pattern) && ! str_starts_with(trim($pattern), '#')
            );
        }
    }

    public function isIgnored(string $path): bool
    {
        foreach ($this->patterns as $pattern) {
            $pattern = trim($pattern);
            $regex = $this->convertGitignoreToRegex($pattern);
            if (preg_match($regex, $path)) {
                return true;
            }
        }

        return false;
    }

    private function convertGitignoreToRegex(string $pattern): string
    {
        $pattern = preg_quote($pattern, '/');
        $pattern = str_replace('\*', '.*', $pattern);
        $pattern = str_replace('\?', '.', $pattern);

        return "/^" . str_replace('/', '\/', $pattern) . "$/";
    }
}
