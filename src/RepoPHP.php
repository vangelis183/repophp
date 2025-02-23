<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Vangelis\RepoPHP\Formatters\JsonFormatter;
use Vangelis\RepoPHP\Formatters\MarkdownFormatter;
use Vangelis\RepoPHP\Formatters\PlainTextFormatter;
use Vangelis\RepoPHP\Formatters\XmlFormatter;

class RepoPHP
{
    private const string FORMAT_PLAIN = 'plain';

    private const string FORMAT_MARKDOWN = 'markdown';

    private const string FORMAT_JSON = 'json';

    private const string FORMAT_XML = 'xml';

    private const array SUPPORTED_FORMATS = [
        self::FORMAT_PLAIN,
        self::FORMAT_MARKDOWN,
        self::FORMAT_JSON,
        self::FORMAT_XML,
    ];

    private const int CHUNK_SIZE = 8192; // 8KB chunks for streaming

    private string $repositoryPath;

    private string $outputPath;

    private string $format;

    private array $excludePatterns;

    private bool $respectGitignore;

    /** @var string[] */
    private array $gitignorePatterns = [];

    private Finder $finder;

    private JsonFormatter $jsonFormatter;

    public function __construct(
        string $repositoryPath,
        string $outputPath,
        string $format = self::FORMAT_PLAIN,
        array $excludePatterns = [],
        bool $respectGitignore = true,
        private ?OutputInterface $output = null
    ) {
        $this->finder = new Finder();
        $this->validatePaths($repositoryPath, $outputPath);
        $this->validateFormat($format);

        $this->repositoryPath = $repositoryPath;
        $this->outputPath = $outputPath;
        $this->format = $format;
        $this->excludePatterns = array_merge([
            'composer.lock',
            'package-lock.json',
            'yarn.lock',
            'pnpm-lock.yaml',
            '.env',
            '.env.*',
            '.DS_Store',
            'Thumbs.db',
            '*.log',
            '.phpunit.cache',
            '.phpunit.result.cache',
            '.php-cs-fixer.cache',
            '.phpcs.cache',
            'docker-compose.override.yml',
        ], $excludePatterns);

        $this->respectGitignore = $respectGitignore;
        $this->jsonFormatter = new JsonFormatter($this->output);
    }

    private function validatePaths(string $repositoryPath, string $outputPath): void
    {
        // Normalize the repository path
        $normalizedPath = realpath($repositoryPath);

        if ($normalizedPath === false || ! is_dir($normalizedPath)) {
            throw new InvalidArgumentException("Repository path '$repositoryPath' does not exist or is not a directory.");
        }

        // Store the normalized path for later use
        $this->repositoryPath = $normalizedPath;

        $outputDir = dirname($outputPath);
        $normalizedOutputDir = realpath($outputDir) ?: $outputDir;

        if (! is_dir($normalizedOutputDir) || ! is_writable($normalizedOutputDir)) {
            throw new InvalidArgumentException("Output directory '$outputDir' does not exist or is not writable.");
        }
    }

    private function validateFormat(string $format): void
    {
        if (! in_array($format, self::SUPPORTED_FORMATS, true)) {
            throw new InvalidArgumentException("Unsupported format '$format'. Supported formats: ".implode(
                ', ',
                self::SUPPORTED_FORMATS
            ));
        }
    }

    public function pack(): void
    {
        // Check and remove existing output file
        if (file_exists($this->outputPath)) {
            if (! unlink($this->outputPath)) {
                throw new RuntimeException("Failed to remove existing output file '{$this->outputPath}'.");
            }
        }

        $files = $this->collectFiles();

        $outputHandle = fopen($this->outputPath, 'wb');
        if ($outputHandle === false) {
            throw new RuntimeException("Failed to open output file '$this->outputPath' for writing.");
        }

        try {
            $this->writeHeader($outputHandle);
            if ($this->output) {
                $this->output->writeln('<info>Starting file processing...</info>');
            }
            foreach ($files as $file) {
                $this->processFile($file, $outputHandle);
            }
            if ($this->format === self::FORMAT_JSON) {
                $this->writeJsonFooter($outputHandle);
            } else {
                $this->writeFooter($outputHandle);
            }

            if ($this->output) {
                $this->output->writeln('<info>File processing completed.</info>');
            }
        } finally {
            fclose($outputHandle);
        }
    }

    private function collectFiles(): array
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

    private function processFile(string $filePath, $outputHandle): void
    {
        if (! is_readable($filePath)) {
            fwrite(STDERR, "Warning: Cannot read file '$filePath', skipping.\n");

            return;
        }

        $relativePath = substr($filePath, strlen($this->repositoryPath) + 1);

        switch ($this->format) {
            case self::FORMAT_PLAIN:
                $this->writeFilePlain($relativePath, $filePath, $outputHandle);

                break;
            case self::FORMAT_MARKDOWN:
                $this->writeFileMarkdown($relativePath, $filePath, $outputHandle);

                break;
            case self::FORMAT_JSON:
                $this->writeFileJson($relativePath, $filePath, $outputHandle);

                break;

            case self::FORMAT_XML:
                $this->writeFileXml($relativePath, $filePath, $outputHandle);

                break;
        }
    }

    private function writeFilePlain(string $relativePath, string $filePath, $outputHandle): void
    {
        $formatter = new PlainTextFormatter($this->output);

        $content = file_get_contents($filePath);

        // Format the file content
        $formattedContent = $formatter->formatFile($relativePath, $content);

        // Write to the output handle
        fwrite($outputHandle, $formattedContent);
        fwrite($outputHandle, $formatter->getSeparator());
    }

    private function writeFileXml(string $relativePath, string $filePath, $outputHandle): void
    {
        $formatter = new XmlFormatter($this->output);

        $content = file_get_contents($filePath);

        // Format the file content
        $formattedContent = $formatter->formatFile($relativePath, $content);

        // Write to the output handle
        fwrite($outputHandle, $formattedContent);
        fwrite($outputHandle, $formatter->getSeparator());
    }

    private function writeFileMarkdown(string $relativePath, string $filePath, $outputHandle): void
    {
        $formatter = new MarkdownFormatter($this->output);

        $content = file_get_contents($filePath);

        // Format the file content
        $formattedContent = $formatter->formatFile($relativePath, $content);

        // Write to the output handle
        fwrite($outputHandle, $formattedContent);
        fwrite($outputHandle, $formatter->getSeparator());
    }

    private function writeFileJson(string $relativePath, string $filePath, $outputHandle): void
    {
        $content = file_get_contents($filePath);

        $this->jsonFormatter->formatFile($relativePath, $content);
    }

    private function writeHeader($outputHandle): void
    {
        $formatter = match ($this->format) {
            self::FORMAT_PLAIN => new PlainTextFormatter($this->output),
            self::FORMAT_MARKDOWN => new MarkdownFormatter($this->output),
            self::FORMAT_JSON => new JsonFormatter($this->output),
            self::FORMAT_XML => new XmlFormatter($this->output),
            default => throw new RuntimeException("Unsupported format: {$this->format}")
        };

        $header = $formatter->getHeader();
        fwrite($outputHandle, $header.$formatter->getSeparator());
    }

    private function writeFooter($outputHandle): void
    {
        $formatter = match ($this->format) {
            self::FORMAT_PLAIN => new PlainTextFormatter(),
            self::FORMAT_MARKDOWN => new MarkdownFormatter(),
            self::FORMAT_XML => new XmlFormatter(),
            default => throw new RuntimeException("Unsupported format: {$this->format}")
        };

        $footer = $formatter->getFooter();
        fwrite($outputHandle, $footer);
    }

    // Add this method to write the final JSON output
    private function writeJsonFooter($outputHandle): void
    {
        fwrite($outputHandle, $this->jsonFormatter->getFooter());
    }
}
