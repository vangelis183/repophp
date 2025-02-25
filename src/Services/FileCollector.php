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
        $gitignoreReader = null;

        if ($this->respectGitignore) {
            $gitignoreReader = new GitignoreReader($this->repositoryPath . '/.gitignore');
        }

        $this->finder
            ->ignoreDotFiles(false)
            ->ignoreVCS(true)
            ->in($this->repositoryPath)
            ->notName($this->excludePatterns)
            ->sortByName();

        foreach ($this->finder as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relativePath = str_replace($this->repositoryPath . '/', '', $file->getPathname());

            if ($gitignoreReader && $gitignoreReader->isIgnored($relativePath)) {
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
