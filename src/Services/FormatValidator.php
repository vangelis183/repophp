<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Services;

use Vangelis\RepoPHP\Config\RepoPHPConfig;
use Vangelis\RepoPHP\Exceptions\UnsupportedFormatException;

class FormatValidator
{
    public function validate(string $format): void
    {
        if (! in_array($format, RepoPHPConfig::SUPPORTED_FORMATS, true)) {
            throw new UnsupportedFormatException(
                "Unsupported format '$format'. Supported formats: " . implode(', ', RepoPHPConfig::SUPPORTED_FORMATS)
            );
        }
    }
}
