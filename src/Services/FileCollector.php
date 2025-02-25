<?php

namespace Vangelis\RepoPHP\Services;

use Symfony\Component\Finder\Finder;

readonly class FileCollector
{
    public function __construct(
        private Finder $finder,
        private array $excludePatterns,
        private bool $respectGitignore,
        private string $repositoryPath,
    ) {
    }

    public function collectFiles(): array
    {
        $files = [];
        $this->finder
            ->ignoreDotFiles(false)
            ->ignoreVCS(true)
            ->ignoreVCSIgnored($this->respectGitignore)
            ->in($this->repositoryPath)
            ->notName($this->excludePatterns)
            ->sortByName();

        foreach ($this->finder as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $folderName = str_replace($this->repositoryPath . '/', '', $file->getPath());
            $files[$folderName][] = $file->getPathname();
        }

        // Sort by folder names
        ksort($files);

        // Flatten the array while maintaining folder order
        return array_merge(...array_values($files));
    }
}
