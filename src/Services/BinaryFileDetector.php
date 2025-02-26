<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Services;

class BinaryFileDetector
{
    private array $mimeTypeCache = [];

    public function isBinary(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        if (isset($this->mimeTypeCache[$filePath])) {
            return $this->mimeTypeCache[$filePath];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        $isBinary = !str_starts_with($mimeType, 'text/')
            && !in_array($mimeType, [
                'application/x-httpd-php',
                'application/json',
                'application/xml',
                'application/javascript',
                'application/x-javascript',
                'application/ecmascript',
                'application/x-yaml',
                'application/x-perl',
                'application/x-sh',
                'application/x-ruby',
                'application/x-python',
            ], true);

        $this->mimeTypeCache[$filePath] = $isBinary;

        return $isBinary;
    }
}
