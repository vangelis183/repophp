<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Config;

class RepoPHPConfig
{
    public const CHUNK_SIZE = 8192;

    public const FORMAT_PLAIN = 'plain';
    public const FORMAT_MARKDOWN = 'markdown';
    public const FORMAT_JSON = 'json';
    public const FORMAT_XML = 'xml';

    public const SUPPORTED_FORMATS = [
        self::FORMAT_PLAIN,
        self::FORMAT_MARKDOWN,
        self::FORMAT_JSON,
        self::FORMAT_XML,
    ];

    private array $excludePatterns;
    private bool $respectGitignore;
    private string $format;

    public function __construct(
        string $format = self::FORMAT_PLAIN,
        array $excludePatterns = [],
        bool $respectGitignore = true
    ) {
        $this->format = $format;
        $this->excludePatterns = array_merge(self::getDefaultExcludePatterns(), $excludePatterns);
        $this->respectGitignore = $respectGitignore;
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
