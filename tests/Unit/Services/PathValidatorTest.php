<?php

namespace Vangelis\RepoPHP\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Vangelis\RepoPHP\Exceptions\InvalidPathException;
use Vangelis\RepoPHP\Services\PathValidator;

class PathValidatorTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/repophp-pathvalidator-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testValidateRepositoryPath()
    {
        $validator = new PathValidator();
        $result = $validator->validateRepositoryPath($this->tempDir);
        $this->assertEquals(realpath($this->tempDir), $result);
    }

    public function testValidateRepositoryPathThrowsExceptionForNonExistentPath()
    {
        $validator = new PathValidator();
        $nonExistentPath = $this->tempDir . '/non_existent';

        $this->expectException(InvalidPathException::class); // Korrigiert auf die tatsächlich geworfene Exception
        $validator->validateRepositoryPath($nonExistentPath);
    }

    public function testValidateOutputPath()
    {
        $validator = new PathValidator();
        $outputPath = $this->tempDir . '/output.txt';

        $result = $validator->validateOutputPath($outputPath);
        $this->assertEquals($outputPath, $result);
    }
}
