<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Services;

use Symfony\Component\Console\Output\OutputInterface;
use Vangelis\RepoPHP\Config\RepoPHPConfig;
use Vangelis\RepoPHP\Exceptions\FileWriteException;
use Vangelis\RepoPHP\Factory\FormatterFactory;

class FileWriter
{
    private int $totalFiles = 0;

    private int $totalChars = 0;

    private readonly ?OutputInterface $output;

    private readonly string $outputPath;

    private readonly RepoHelper $repoHelper;

    public function __construct(
        private readonly FormatterFactory $formatterFactory,
        private readonly RepoPHPConfig $config,
        ?OutputInterface $output = null,
        string $outputPath = '',
        string $repositoryPath = ''
    ) {
        $this->output = $output;
        $this->outputPath = $outputPath;
        $this->repoHelper = new RepoHelper($repositoryPath, $output);
    }

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

    public function writeContent($outputHandle, string $relativePath, string $filePath): void
    {
        if (! is_readable($filePath)) {
            $this->output?->writeln("<error>⚠️  Cannot read file: {$relativePath}</error>");

            return;
        }

        $content = $this->readFile($filePath);
        $this->totalChars += strlen($content);
        $this->totalFiles++;

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
            $this->output->writeln(sprintf("       Output: %s", basename($this->outputPath)));

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
                    $this->output->writeln("     Remotes: " . implode(', ', $remoteInfo));
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
