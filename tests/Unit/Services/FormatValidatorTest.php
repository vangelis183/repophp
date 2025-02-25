<?php

namespace Vangelis\RepoPHP\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Vangelis\RepoPHP\Config\RepoPHPConfig;
use Vangelis\RepoPHP\Services\FormatValidator;
use Vangelis\RepoPHP\Exceptions\UnsupportedFormatException;

class FormatValidatorTest extends TestCase
{
    private FormatValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new FormatValidator();
    }

    public function testValidateWithValidFormat()
    {
        // Sollte keine Exception werfen
        $this->validator->validate(RepoPHPConfig::FORMAT_PLAIN);
        $this->validator->validate(RepoPHPConfig::FORMAT_MARKDOWN);
        $this->assertTrue(true); // Wenn wir bis hier kommen, ist der Test erfolgreich
    }

    public function testValidateWithInvalidFormat()
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->validator->validate('invalid_format');
    }
}
