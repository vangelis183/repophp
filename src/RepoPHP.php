<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Vangelis\RepoPHP\Analyzers\TokenCounter;
use Vangelis\RepoPHP\Config\RepoPHPConfig;
use Vangelis\RepoPHP\Exceptions\FileWriteException;
use Vangelis\RepoPHP\Factory\FormatterFactory;
use Vangelis\RepoPHP\Services\FileCollector;
use Vangelis\RepoPHP\Services\FileWriter;
use Vangelis\RepoPHP\Services\FormatValidator;
use Vangelis\RepoPHP\Services\PathValidator;
use Vangelis\RepoPHP\Services\GitDiffService;

class RepoPHP
{
    private readonly RepoPHPConfig $config;
    private readonly FileWriter $fileWriter;
    private readonly FileCollector $fileCollector;
    private readonly string $repositoryPath;
    private string $outputPath;
    private readonly FormatterFactory $formatterFactory;
    private readonly FormatValidator $formatValidator;
    private readonly PathValidator $pathValidator;
    private readonly TokenCounter $tokenCounter;
    private readonly ?OutputInterface $output;
    private bool $incrementalMode = false;
    private ?string $baseFilePath = null;
    private ?GitDiffService $gitDiffService = null;

    public function __construct(
        string $repositoryPath,
        string $outputPath,
        string $format = RepoPHPConfig::FORMAT_PLAIN,
        array $excludePatterns = [],
        bool $respectGitignore = true,
        ?OutputInterface $output = null,
        string $encoding = RepoPHPConfig::ENCODING_CL100K,
        bool $compress = false,
        ?string $tokenCounterPath = null,
        int $maxTokensPerFile = 0,
        bool $incrementalMode = false,
        ?string $baseFilePath = null
    ) {
        $this->output = $output;
        $this->pathValidator = new PathValidator();
        $this->formatValidator = new FormatValidator();
        $this->formatterFactory = new FormatterFactory();

        $this->repositoryPath = $this->pathValidator->validateRepositoryPath($repositoryPath);
        $this->outputPath = $this->pathValidator->validateOutputPath($outputPath);

        $this->config = new RepoPHPConfig($format, $excludePatterns, $respectGitignore, $tokenCounterPath, $encoding, $compress, $maxTokensPerFile);
        $this->tokenCounter = new TokenCounter($tokenCounterPath ?? $this->config->getTokenCounterPath());

        $this->formatValidator->validate($this->config->getFormat());

        $this->fileWriter = new FileWriter(
            $this->formatterFactory,
            $this->config,
            $this->tokenCounter,
            $output,
            $this->outputPath,
            $this->repositoryPath
        );
        $this->fileCollector = new FileCollector(new Finder(), $this->config->getExcludePatterns(), $this->config->getRespectGitignore(), $this->repositoryPath);

        $this->incrementalMode = $incrementalMode;
        $this->baseFilePath = $baseFilePath;

        if ($incrementalMode) {
            $this->gitDiffService = new GitDiffService($repositoryPath);
        }
    }

    public function pack(): void
    {
        if ($this->incrementalMode && !$this->baseFilePath) {
            throw new \InvalidArgumentException('Base file is required for incremental packing');
        }

        $files = $this->incrementalMode
            ? $this->getChangedFiles()
            : $this->fileCollector->collectFiles();

        $fileIndex = 0;
        $currentTokens = 0;
        $maxTokens = $this->config->getMaxTokensPerFile();
        $outputBasePath = $this->outputPath;

        if ($this->incrementalMode) {
            $pathInfo = pathinfo($outputBasePath);
            $outputBasePath = sprintf(
                '%s/%s_diff_%s.%s',
                $pathInfo['dirname'],
                $pathInfo['filename'],
                date('Y-m-d_His'),
                $pathInfo['extension'] ?? 'txt'
            );
        }

        $this->outputPath = $this->getOutputFilePath($outputBasePath, $fileIndex);
        $outputHandle = $this->openOutputFile();
        $processedFiles = 0;

        try {
            $this->fileWriter->writeHeader($outputHandle);

            foreach ($files as $file) {
                $relativePath = str_replace($this->repositoryPath . '/', '', $file);

                $fileTokens = $this->tokenCounter->countTokens($file, $this->config->getEncoding());
                $relativePath = str_replace($this->repositoryPath . '/', '', $file);
                if ($maxTokens > 0 && $processedFiles > 0 && ($currentTokens + $fileTokens) > $maxTokens) {
                    $this->fileWriter->writeFooter($outputHandle);
                    fclose($outputHandle);

                    $fileIndex++;
                    $this->outputPath = $this->getOutputFilePath($outputBasePath, $fileIndex);
                    $outputHandle = $this->openOutputFile();
                    $this->fileWriter->resetStats();
                    $this->fileWriter->writeHeader($outputHandle);
                    $currentTokens = 0;

                    if ($this->output) {
                        $this->output->writeln("\n📦 Starting new file due to token limit: {$this->outputPath}\n");
                    }
                }
                $this->fileWriter->writeContent($outputHandle, $relativePath, $file);
                $currentTokens += $fileTokens;
                $processedFiles++;
            }

            if ($this->incrementalMode) {
                $this->fileWriter->setIncrementalInfo([
                    'baseFile' => $this->baseFilePath,
                    'baseCommit' => $this->gitDiffService->getLastPackCommit($this->baseFilePath),
                    'changedFiles' => count($files),
                ]);
            }

            $this->fileWriter->writeFooter($outputHandle);
        } finally {
            if (is_resource($outputHandle)) {
                fclose($outputHandle);
            }
        }
        if ($this->output && $fileIndex > 0) {
            $this->output->writeln("\n🔄 Repository was split into " . ($fileIndex + 1) . " files due to token limit");
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

    private function getOutputFilePath(string $basePath, int $index): string
    {
        if ($index === 0) {
            return $basePath;
        }

        $pathInfo = pathinfo($basePath);

        return sprintf(
            '%s/%s-part%d.%s',
            $pathInfo['dirname'],
            $pathInfo['filename'],
            $index + 1,
            $pathInfo['extension']
        );
    }

    private function getChangedFiles(): array
    {
        if (!$this->gitDiffService) {
            return [];
        }

        $baseCommit = $this->gitDiffService->getLastPackCommit($this->baseFilePath);
        if (!$baseCommit) {
            throw new \RuntimeException('Could not determine base commit from the base file');
        }

        $changedFilePaths = $this->gitDiffService->getChangedFilesSinceCommit($baseCommit);

        $fullPathFiles = [];
        foreach ($changedFilePaths as $relativePath) {
            $fullPath = $this->repositoryPath . '/' . $relativePath;
            if (file_exists($fullPath) && is_file($fullPath)) {
                $fullPathFiles[] = $fullPath;
            }
        }

        return $fullPathFiles;
    }
}
