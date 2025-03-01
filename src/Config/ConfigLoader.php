<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Config;

class ConfigLoader
{
    private const CONFIG_FILENAMES = [
        '.repophp.json',
        'repophp.json',
        '.repophp.config.json',
        'repophp.config.json'
    ];

    /**
     * Load configuration from file in current directory
     */
    public static function loadConfig(): array
    {
        $configPath = self::findConfigFile();
        if (!$configPath) {
            return [];
        }

        $configContent = file_get_contents($configPath);
        if ($configContent === false) {
            return [];
        }

        $config = json_decode($configContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON in config file: ' . json_last_error_msg());
        }

        return $config;
    }

    /**
     * Find configuration file in current directory
     */
    private static function findConfigFile(): ?string
    {
        $currentDir = getcwd();

        foreach (self::CONFIG_FILENAMES as $filename) {
            $path = $currentDir . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
