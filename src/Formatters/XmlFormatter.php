<?php

namespace Vangelis\RepoPHP\Formatters;

use Symfony\Component\Console\Output\OutputInterface;

class XmlFormatter extends BaseFormatter
{
    public function __construct(?OutputInterface $output = null)
    {
        parent::__construct($output);
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
        if ($this->output) {
            $this->output->writeln(sprintf(
                '<comment>Adding to XML: %s (size: %d bytes)</comment>',
                $this->formatPath($path),
                strlen($content)
            ));
        }

        $escapedContent = htmlspecialchars($content, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $escapedPath = htmlspecialchars($this->formatPath($path), ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return <<<EOT
    <file>
        <path>{$escapedPath}</path>
        <content><![CDATA[{$escapedContent}]]></content>
    </file>
EOT;
    }
}
