<?php

namespace Vangelis\RepoPHP\Services;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Vangelis\RepoPHP\Formatters\FormatterInterface;
use Vangelis\RepoPHP\Formatters\JsonFormatter;
use Vangelis\RepoPHP\Formatters\MarkdownFormatter;
use Vangelis\RepoPHP\Formatters\XmlFormatter;

class RepoHelper
{
    private string $repositoryPath;

    public function __construct(
        string $repositoryPath,
        private ?OutputInterface $output = null
    ) {
        $this->repositoryPath = $repositoryPath;
    }

    public function getGitInfo(): array
    {
        $gitInfo = [
            'branch' => '',
            'commit' => [
                'hash' => '',
                'author' => '',
                'email' => '',
                'message' => '',
            ],
            'remotes' => [],
            'status' => [],
        ];

        // Check if it's a git repository
        if (! is_dir($this->repositoryPath.'/.git')) {
            return $gitInfo;
        }

        try {
            // Get current branch
            $process = new Process([
                'git',
                'branch',
                '--show-current',
            ], $this->repositoryPath);
            $process->run();
            if ($process->isSuccessful()) {
                $gitInfo['branch'] = trim($process->getOutput());
            }

            // Get last commit info
            $process = new Process([
                'git',
                'log',
                '-1',
                '--format=%H|%an|%ae|%s',
            ], $this->repositoryPath);
            $process->run();
            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());
                if (! empty($output)) {
                    $parts = explode('|', $output);
                    if (count($parts) === 4) {
                        [
                            $hash,
                            $author,
                            $email,
                            $message,
                        ] = $parts;
                        $gitInfo['commit'] = [
                            'hash' => $hash,
                            'author' => $author,
                            'email' => $email,
                            'message' => $message,
                        ];
                    }
                }
            }

            // Get remotes
            $process = new Process([
                'git',
                'remote',
                '-v',
            ], $this->repositoryPath);
            $process->run();
            if ($process->isSuccessful()) {
                $remotes = [];
                foreach (explode("\n", trim($process->getOutput())) as $line) {
                    if (preg_match('/^(\S+)\s+(\S+)\s+\((fetch|push)\)$/', $line, $matches)) {
                        $remotes[$matches[1]][$matches[3]] = $matches[2];
                    }
                }
                $gitInfo['remotes'] = $remotes;
            }

            // Get status
            $process = new Process([
                'git',
                'status',
                '--porcelain',
            ], $this->repositoryPath);
            $process->run();
            if ($process->isSuccessful()) {
                $gitInfo['status'] = array_filter(explode("\n", trim($process->getOutput())));
            }
        } catch (Exception $e) {
            // Log the error if you have a logger
            if ($this->output) {
                $this->output->writeln('<error>Error while getting git info: '.$e->getMessage().'</error>');
            }

            // Return the default structure if something goes wrong
            return $gitInfo;
        }

        return $gitInfo;
    }

    private function formatGitInfo(array $gitInfo, FormatterInterface $formatter): string
    {
        // Format git info based on the formatter type
        if ($formatter instanceof JsonFormatter) {
            // JSON formatter handles git info in its metadata
            return '';
        }

        if ($formatter instanceof MarkdownFormatter) {
            return $this->formatGitInfoMarkdown($gitInfo);
        }

        if ($formatter instanceof XmlFormatter) {
            return $this->formatGitInfoXml($gitInfo);
        }

        // PlainTextFormatter
        return $this->formatGitInfoPlain($gitInfo);
    }

    private function formatGitInfoMarkdown(array $gitInfo): string
    {
        $output = "## Git Repository Information\n\n";
        if (isset($gitInfo['branch'])) {
            $output .= "- **Current Branch:** {$gitInfo['branch']}\n";
        }
        if (isset($gitInfo['commit'])) {
            $output .= "- **Latest Commit:**\n";
            $output .= "  - Hash: {$gitInfo['commit']['hash']}\n";
            $output .= "  - Author: {$gitInfo['commit']['author']}\n";
            $output .= "  - Message: {$gitInfo['commit']['message']}\n";
        }

        return $output;
    }

    private function formatGitInfoXml(array $gitInfo): string
    {
        $output = "    <git-info>\n";
        if (isset($gitInfo['branch'])) {
            $output .= "        <branch>" . htmlspecialchars($gitInfo['branch']) . "</branch>\n";
        }
        if (isset($gitInfo['commit'])) {
            $output .= "        <commit>\n";
            $output .= "            <hash>" . htmlspecialchars($gitInfo['commit']['hash']) . "</hash>\n";
            $output .= "            <author>" . htmlspecialchars($gitInfo['commit']['author']) . "</author>\n";
            $output .= "            <message>" . htmlspecialchars($gitInfo['commit']['message']) . "</message>\n";
            $output .= "        </commit>\n";
        }
        $output .= "    </git-info>";

        return $output;
    }

    private function formatGitInfoPlain(array $gitInfo): string
    {
        $output = "Git Repository Information\n";
        $output .= "========================\n\n";
        if (isset($gitInfo['branch'])) {
            $output .= "Current Branch: {$gitInfo['branch']}\n";
        }
        if (isset($gitInfo['commit'])) {
            $output .= "Latest Commit:\n";
            $output .= "  Hash: {$gitInfo['commit']['hash']}\n";
            $output .= "  Author: {$gitInfo['commit']['author']}\n";
            $output .= "  Message: {$gitInfo['commit']['message']}\n";
        }

        return $output;
    }
}
