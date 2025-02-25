<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Services;

use Vangelis\RepoPHP\Exceptions\InvalidPathException;

class PathValidator
{
    /**
     * @throws \Vangelis\RepoPHP\Exceptions\InvalidPathException
     */
    public function validateRepositoryPath(string $repositoryPath): string
    {
        $normalizedPath = realpath($repositoryPath);

        if ($normalizedPath === false || ! is_dir($normalizedPath)) {
            throw new InvalidPathException("Repository path '$repositoryPath' does not exist or is not a directory.");
        }

        return $normalizedPath;
    }

    /**
     * @throws \Vangelis\RepoPHP\Exceptions\InvalidPathException
     */
    public function validateOutputPath(string $outputPath): string
    {
        $outputDir = dirname($outputPath);
        $normalizedOutputDir = realpath($outputDir) ?: $outputDir;

        if (! is_dir($normalizedOutputDir) || ! is_writable($normalizedOutputDir)) {
            throw new InvalidPathException("Output directory '$outputDir' does not exist or is not writable.");
        }

        return $outputPath;
    }
}
