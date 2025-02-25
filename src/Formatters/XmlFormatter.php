<?php

namespace Vangelis\RepoPHP\Formatters;

class XmlFormatter extends BaseFormatter
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getHeader(): string
    {
        $dateTime = $this->getDateTime();

        return <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<repository>
    <metadata>
        <title>Repository Export</title>
        <generated_at>{$dateTime}</generated_at>
    </metadata>
EOT;
    }

    public function getFooter(): string
    {
        return '</repository>';
    }

    public function formatFile(string $path, string $content): string
    {
        $escapedPath = htmlspecialchars($this->formatPath($path), ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return <<<EOT
    <file>
        <path>{$escapedPath}</path>
        <content><![CDATA[{$content}]]></content>
    </file>
EOT;
    }
}
