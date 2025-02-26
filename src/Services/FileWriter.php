<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Services;

use Symfony\Component\Console\Output\OutputInterface;
use Vangelis\RepoPHP\Analyzers\TokenCounter;
use Vangelis\RepoPHP\Config\RepoPHPConfig;
use Vangelis\RepoPHP\Exceptions\FileWriteException;
use Vangelis\RepoPHP\Factory\FormatterFactory;

class FileWriter
{
    private int $totalFiles = 0;

    private int $totalChars = 0;

    private int $totalTokens = 0;

    private array $fileStats = [];

    private array $unreadableFiles = [];

    private array $binaryFiles = [];

    private readonly ?OutputInterface $output;

    private readonly string $outputPath;

    private readonly RepoHelper $repoHelper;

    private readonly CommentStripper $commentStripper;

    private readonly BinaryFileDetector $binaryFileDetector;

    public function __construct(
        private readonly FormatterFactory $formatterFactory,
        private readonly RepoPHPConfig $config,
        private readonly TokenCounter $tokenCounter,
        ?OutputInterface $output = null,
        string $outputPath = '',
        string $repositoryPath = ''
    ) {
        $this->output = $output;
        $this->outputPath = $outputPath;
        $this->repoHelper = new RepoHelper($repositoryPath, $output);
        $this->commentStripper = new CommentStripper();
        $this->binaryFileDetector = new BinaryFileDetector();
    }

    /**
     * @throws \Vangelis\RepoPHP\Exceptions\UnsupportedFormatException
     */
    public function writeHeader($outputHandle): void
    {
        if ($this->output) {
            $this->output->writeln("\n📦 Starting to pack files...\n");
        }

        $formatter = $this->formatterFactory->createFormatter($this->config->getFormat());
        $header = $formatter->getHeader();

        $gitInfo = $this->repoHelper->formatRepositoryInfo($this->config->getFormat());
        if ($gitInfo) {
            if ($this->output) {
                $this->output->writeln("🔍 Git repository detected");
            }
            $header .= $gitInfo;
        }

        fwrite($outputHandle, $header.$formatter->getSeparator());
    }

    /**
     * @throws \Vangelis\RepoPHP\Exceptions\UnsupportedFormatException
     * @throws \Vangelis\RepoPHP\Exceptions\FileWriteException
     */
    public function writeContent($outputHandle, string $relativePath, string $filePath): void
    {
        if (! is_readable($filePath)) {
            $this->unreadableFiles[] = $relativePath;
            $this->output?->writeln("<error>⚠️  Cannot read file: {$relativePath}</error>");

            return;
        }

        if ($this->binaryFileDetector->isBinary($filePath)) {
            $this->binaryFiles[] = $relativePath;
            $this->output?->writeln("<error>⚠️  Skipping binary file: {$relativePath}</error>");

            return;
        }

        $content = $this->readFile($filePath);

        if ($this->config->shouldCompress()) {
            $content = $this->commentStripper->cleanFile($content) ?? $content;
        }

        $chars = strlen($content);

        $tokens = $this->tokenCounter->countTokens($filePath, $this->config->getEncoding());

        $this->totalChars += $chars;
        $this->totalTokens += $tokens;
        $this->totalFiles++;

        $this->fileStats[] = [
            'path' => $relativePath,
            'chars' => $chars,
            'tokens' => $tokens,
        ];

        if ($this->output) {
            $this->output->writeln(sprintf(
                "✨ Adding: <info>%s</info> (%s chars)",
                $relativePath,
                number_format(strlen($content), 0, '.', ',')
            ));
        }

        $formatter = $this->formatterFactory->createFormatter($this->config->getFormat());
        $formattedContent = $formatter->formatFile($relativePath, $content);

        fwrite($outputHandle, $formattedContent);
        fwrite($outputHandle, $formatter->getSeparator());
    }

    public function writeFooter($outputHandle): void
    {
        $formatter = $this->formatterFactory->createFormatter($this->config->getFormat());
        $footer = $formatter->getFooter();
        fwrite($outputHandle, $footer);

        if ($this->output) {
            $gitInfo = $this->repoHelper->getGitInfo();

            $this->output->writeln("\n📊 Pack Summary:");
            $this->output->writeln("────────────────");
            $this->output->writeln(sprintf("  Total Files: %s files", number_format($this->totalFiles, 0, '.', '.')));
            $this->output->writeln(sprintf("  Total Chars: %s chars", number_format($this->totalChars, 0, '.', '.')));
            $this->output->writeln(sprintf(" Total Tokens: %s tokens", number_format($this->totalTokens, 0, '.', '.')));
            $this->output->writeln(sprintf("     Encoding: %s", $this->config->getEncoding()));
            $this->output->writeln(sprintf("       Output: %s", basename($this->outputPath)));

            // Sort files by tokens count
            usort($this->fileStats, fn ($a, $b) => $b['tokens'] <=> $a['tokens']);
            $top5 = array_slice($this->fileStats, 0, 5);

            $this->output->writeln("\n📈 Top 5 Files by Character Count and Token Count:");
            $this->output->writeln("──────────────────────────────────────────────────");
            foreach ($top5 as $i => $stat) {
                $this->output->writeln(sprintf(
                    "%d.  %s (%s chars, %s tokens)",
                    $i + 1,
                    $stat['path'],
                    number_format($stat['chars'], 0, '.', ','),
                    number_format($stat['tokens'], 0, '.', ',')
                ));
            }

            if (! empty($gitInfo['branch'])) {
                $this->output->writeln("\n📌 Git Info:");
                $this->output->writeln("────────────");
                $this->output->writeln(sprintf("      Branch: %s", $gitInfo['branch']));
                if (! empty($gitInfo['commit'])) {
                    $this->output->writeln(sprintf("      Commit: %s", substr($gitInfo['commit']['hash'], 0, 7)));
                    $this->output->writeln(sprintf("      Author: %s", $gitInfo['commit']['author']));
                }
                if (! empty($gitInfo['remotes'])) {
                    $remoteInfo = [];
                    foreach ($gitInfo['remotes'] as $name => $urls) {
                        $remoteInfo[] = sprintf("%s (%s)", $name, $urls['fetch']);
                    }
                    $this->output->writeln("     Remotes: ".implode(', ', $remoteInfo));
                }
            }

            if (! empty($this->unreadableFiles) || ! empty($this->binaryFiles)) {
                $this->output->writeln("\n⚠️  Unprocessed Files:");
                $this->output->writeln("───────────────────────");

                if (! empty($this->unreadableFiles)) {
                    $this->output->writeln("\n  Unreadable Files:");
                    foreach ($this->unreadableFiles as $file) {
                        $this->output->writeln("  - {$file}");
                    }
                }

                if (! empty($this->binaryFiles)) {
                    $this->output->writeln("\n  Binary Files:");
                    foreach ($this->binaryFiles as $file) {
                        $this->output->writeln("  - {$file}");
                    }
                }
            }

            $this->output->writeln('');
        }
    }

    private function readFile(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new FileWriteException("Failed to read file: $filePath");
        }

        return $content;
    }
}
