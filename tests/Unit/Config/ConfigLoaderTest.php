<?php

namespace Vangelis\RepoPHP\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Vangelis\RepoPHP\Config\ConfigLoader;

class ConfigLoaderTest extends TestCase
{
    private string $tempDir;
    private string $originalDir;

    protected function setUp(): void
    {
        $this->originalDir = getcwd();
        $this->tempDir = sys_get_temp_dir() . '/repophp_config_test_' . uniqid();
        mkdir($this->tempDir);
        chdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalDir);
        if (file_exists($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    public function testLoadsConfigFromFile(): void
    {
        $config = [
            'repository' => '/path/to/repo',
            'format' => 'markdown',
            'encoding' => 'cl100k_base',
        ];

        file_put_contents($this->tempDir . '/.repophp.json', json_encode($config));

        $loadedConfig = ConfigLoader::loadConfig();
        $this->assertEquals($config, $loadedConfig);
    }

    public function testReturnsEmptyArrayIfNoConfigFile(): void
    {
        $this->assertEquals([], ConfigLoader::loadConfig());
    }

    private function removeDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
