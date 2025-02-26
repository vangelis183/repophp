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
        // Remove trailing spaces
        $pattern = trim($pattern);

        // Escape special regex characters, but not the gitignore wildcards
        $pattern = preg_quote($pattern, '/');

        // Convert gitignore wildcards to regex patterns
        $pattern = str_replace('\*\*', '.*', $pattern); // Handle ** first
        $pattern = str_replace('\*', '[^/]*', $pattern); // Single * doesn't match /
        $pattern = str_replace('\?', '[^/]', $pattern); // ? matches single character except /

        // Handle directory separator
        $pattern = str_replace('/', '\/', $pattern);

        // Add start and end anchors
        return "/^{$pattern}$/";
    }
}
