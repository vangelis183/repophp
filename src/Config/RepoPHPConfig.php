<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Config;

use Vangelis\RepoPHP\Exceptions\TokenCounterException;

class RepoPHPConfig
{
    private const TOKEN_COUNTER_BINARY = 'token-counter';
    public const FORMAT_PLAIN = 'plain';
    public const FORMAT_MARKDOWN = 'markdown';
    public const FORMAT_JSON = 'json';
    public const FORMAT_XML = 'xml';

    public const ENCODING_CL100K = 'cl100k_base';
    public const ENCODING_P50K = 'p50k_base';
    public const ENCODING_R50K = 'r50k_base';
    public const ENCODING_P50K_EDIT = 'p50k_edit';

    public const SUPPORTED_FORMATS = [
        self::FORMAT_PLAIN,
        self::FORMAT_MARKDOWN,
        self::FORMAT_JSON,
        self::FORMAT_XML,
    ];

    public const SUPPORTED_ENCODINGS = [
        self::ENCODING_CL100K => 'GPT-4, GPT-4o, GPT-3.5-Turbo',
        self::ENCODING_P50K => 'GPT-3 models',
        self::ENCODING_R50K => 'Davinci models',
        self::ENCODING_P50K_EDIT => 'Text-edit models',
    ];

    private array $excludePatterns;
    private bool $respectGitignore;
    private string $format;

    private string $tokenCounterPath;
    private string $encoding;

    private bool $compress;

    private int $maxTokensPerFile;

    public function __construct(
        string $format = self::FORMAT_PLAIN,
        array $excludePatterns = [],
        bool $respectGitignore = true,
        ?string $tokenCounterPath = null,
        string $encoding = self::ENCODING_CL100K,
        bool $compress = false,
        int $maxTokensPerFile = 0
    ) {
        $this->format = $format;
        $this->excludePatterns = array_merge(self::getDefaultExcludePatterns(), $excludePatterns);
        $this->respectGitignore = $respectGitignore;
        $this->tokenCounterPath = $tokenCounterPath ?? $this->findTokenCounterBinary();
        $this->encoding = $encoding;
        if (! file_exists($this->tokenCounterPath)) {
            throw new TokenCounterException('Token counter binary not found');
        }
        $this->compress = $compress;
        $this->maxTokensPerFile = $maxTokensPerFile;
    }

    public function getMaxTokensPerFile(): int
    {
        return $this->maxTokensPerFile;
    }

    public function shouldCompress(): bool
    {
        return $this->compress;
    }

    public function getEncoding(): string
    {
        return $this->encoding;
    }

    private function findTokenCounterBinary(): string
    {
        $os = strtolower(PHP_OS);
        if (strpos($os, 'darwin') !== false) {
            $os = 'mac';
        } elseif (strpos($os, 'win') !== false) {
            $os = 'windows';
        } else {
            $os = 'linux';
        }

        $arch = php_uname('m');
        if ($arch === 'x86_64' || $arch === 'amd64') {
            $arch = 'amd64';
        } elseif (strpos($arch, 'arm') !== false || strpos($arch, 'aarch64') !== false) {
            $arch = 'arm64';
        } else {
            $arch = 'amd64'; // Default to amd64 if unsure
        }

        $binaryName = sprintf('%s-%s-%s', self::TOKEN_COUNTER_BINARY, $os, $arch);

    // Add .exe extension for Windows
        if ($os === 'windows') {
            $binaryName .= '.exe';
        }

    // Check vendor/bin first
        $vendorBinPath = dirname(__DIR__, 3) . '/bin/' . $binaryName;
        if (file_exists($vendorBinPath)) {
            return $vendorBinPath;
        }

    // Check package bin directory
        $packageBinPath = dirname(__DIR__, 2) . '/bin/' . $binaryName;
        if (file_exists($packageBinPath)) {
            return $packageBinPath;
        }

        throw new TokenCounterException(
            "Token counter binary not found for your system ($os-$arch). " .
            "Expected binary name: $binaryName"
        );
    }

    public function getTokenCounterPath(): string
    {
        return $this->tokenCounterPath;
    }

    public static function getDefaultExcludePatterns(): array
    {
        return [
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
        ];
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getExcludePatterns(): array
    {
        return $this->excludePatterns;
    }

    public function getRespectGitignore(): bool
    {
        return $this->respectGitignore;
    }
}
