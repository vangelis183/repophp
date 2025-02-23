<?php

namespace Vangelis\RepoPHP\Formatters;

use Symfony\Component\Console\Output\OutputInterface;

class MarkdownFormatter extends BaseFormatter
{
    private ?OutputInterface $output;

    public function __construct(?OutputInterface $output = null)
    {
        $this->output = $output;
    }

    public function getHeader(): string
    {
        return <<<EOT
# Repository Export
Generated: {$this->getDateTime()}

---

EOT;
    }

    public function getFooter(): string
    {
        return "\n---\n*End of Repository Export*\n";
    }

    public function formatFile(string $path, string $content): string
    {
        // Determine language for code block based on file extension
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $language = $this->getLanguageFromExtension($extension);

        if ($this->output) {
            $this->output->writeln(sprintf(
                '<comment>Adding to Markdown: %s (size: %d bytes)</comment>',
                $this->formatPath($path),
                strlen($content)
            ));
        }

        return <<<EOT
### File: {$this->formatPath($path)}
```{$language}
{$content}
```
EOT;
    }

    private function getLanguageFromExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'php' => 'php',
            'js' => 'javascript',
            'ts' => 'typescript',
            'jsx' => 'jsx',
            'tsx' => 'tsx',
            'css' => 'css',
            'scss', 'sass' => 'scss',
            'html' => 'html',
            'json' => 'json',
            'xml' => 'xml',
            'yml', 'yaml' => 'yaml',
            'md' => 'markdown',
            'sql' => 'sql',
            'sh', 'bash' => 'bash',
            'py' => 'python',
            'rb' => 'ruby',
            'java' => 'java',
            'c', 'cpp', 'h', 'hpp' => 'cpp',
            'cs' => 'csharp',
            'go' => 'go',
            'rs' => 'rust',
            default => 'plaintext'
        };
    }
}
