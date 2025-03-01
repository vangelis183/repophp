<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Services;

use Vangelis\RepoPHP\Exceptions\GitRepositoryException;

class GitDiffService
{
    private ?string $repositoryPath;

    public function __construct(?string $repositoryPath = null)
    {
        $this->repositoryPath = $repositoryPath;
    }

    /**
     * Get files changed since the specified commit
     *
     * @param string $baseCommit Base commit hash to compare against
     * @return array<string> List of changed file paths
     * @throws GitRepositoryException
     */
    public function getChangedFilesSinceCommit(string $baseCommit): array
    {
        $this->validateGitRepository();

        $command = sprintf(
            'cd %s && git diff --name-only %s HEAD',
            escapeshellarg($this->repositoryPath),
            escapeshellarg($baseCommit)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new GitRepositoryException("Failed to get diff with base commit: $baseCommit");
        }

        return array_filter($output); // Filter out any empty lines
    }

    /**
     * Get the commit hash from the last pack
     *
     * @param string $baseFilePath Path to the base file
     * @return string|null Commit hash if found, null otherwise
     */
    public function getLastPackCommit(string $baseFilePath): ?string
    {
        if (! file_exists($baseFilePath)) {
            return null;
        }

        $content = file_get_contents($baseFilePath);
        if ($content === false) {
            return null;
        }

        // Extract commit hash from the file header
        if (preg_match('/Commit: ([a-f0-9]{7,40})/i', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Validate that the repository path is a git repository
     *
     * @throws GitRepositoryException
     */
    private function validateGitRepository(): void
    {
        if (! $this->repositoryPath || ! is_dir($this->repositoryPath)) {
            throw new GitRepositoryException('Repository path is not valid');
        }

        if (! is_dir($this->repositoryPath . '/.git')) {
            throw new GitRepositoryException('Not a git repository');
        }
    }
}
