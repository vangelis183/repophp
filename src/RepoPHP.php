<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP;

use Vangelis\RepoPHP\Analyzers\TokenCounter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Vangelis\RepoPHP\Config\RepoPHPConfig;
use Vangelis\RepoPHP\Exceptions\FileWriteException;
use Vangelis\RepoPHP\Factory\FormatterFactory;
use Vangelis\RepoPHP\Services\FileCollector;
use Vangelis\RepoPHP\Services\FileWriter;
use Vangelis\RepoPHP\Services\FormatValidator;
use Vangelis\RepoPHP\Services\PathValidator;

class RepoPHP
{
    private readonly RepoPHPConfig $config;
    private readonly FileWriter $fileWriter;
    private readonly FileCollector $fileCollector;
    private readonly string $repositoryPath;
    private readonly string $outputPath;
    private readonly FormatterFactory $formatterFactory;
    private readonly FormatValidator $formatValidator;
    private readonly PathValidator $pathValidator;
    // @phpstan-ignore-next-line
    private readonly ?OutputInterface $output;

    public function __construct(
        string $repositoryPath,
        string $outputPath,
        string $format = RepoPHPConfig::FORMAT_PLAIN,
        array $excludePatterns = [],
        bool $respectGitignore = true,
        ?OutputInterface $output = null,
        string $encoding = RepoPHPConfig::ENCODING_CL100K
    ) {
        $this->output = $output;
        $this->pathValidator = new PathValidator();
        $this->formatValidator = new FormatValidator();
        $this->formatterFactory = new FormatterFactory();

        $this->repositoryPath = $this->pathValidator->validateRepositoryPath($repositoryPath);
        $this->outputPath = $this->pathValidator->validateOutputPath($outputPath);

        $this->config = new RepoPHPConfig($format, $excludePatterns, $respectGitignore, null, $encoding);
        $tokenCounter = new TokenCounter($this->config->getTokenCounterPath());

        $this->formatValidator->validate($this->config->getFormat());

        $this->fileWriter = new FileWriter(
            $this->formatterFactory,
            $this->config,
            $tokenCounter,
            $output,
            $this->outputPath,
            $this->repositoryPath
        );
        $this->fileCollector = new FileCollector(new Finder(), $this->config->getExcludePatterns(), $this->config->getRespectGitignore(), $this->repositoryPath);
    }

    public function pack(): void
    {
        $files = $this->fileCollector->collectFiles();
        $outputHandle = $this->openOutputFile();

        try {
            $this->fileWriter->writeHeader($outputHandle);

            foreach ($files as $file) {
                $relativePath = str_replace($this->repositoryPath . '/', '', $file);
                $content = file_get_contents($file);
                $this->fileWriter->writeContent($outputHandle, $relativePath, $file);
            }

            $this->fileWriter->writeFooter($outputHandle);

        } finally {
            fclose($outputHandle);
        }
    }

    private function openOutputFile()
    {
        $handle = fopen($this->outputPath, 'wb');
        if ($handle === false) {
            throw new FileWriteException("Cannot open output file: {$this->outputPath}");
        }

        return $handle;
    }
}
