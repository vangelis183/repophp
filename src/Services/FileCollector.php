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
        $this->finder->ignoreDotFiles(false)->ignoreVCS(true)->ignoreVCSIgnored($this->respectGitignore)->in($this->repositoryPath)->notName($this->excludePatterns);

        foreach ($this->finder as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        return $files;
    }
}
