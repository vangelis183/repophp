<?php

namespace Vangelis\RepoPHP\Services;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class RepoHelper
{
    private string $repositoryPath;

    private ?OutputInterface $output;

    public function __construct(string $repositoryPath, ?OutputInterface $output = null)
    {
        $this->repositoryPath = $repositoryPath;
        $this->output = $output;
    }

    public function getGitInfo(): array
    {
        $gitInfo = [
            'branch' => '',
            'commit' => [],
            'remotes' => [],
            'status' => [],
        ];

        try {
            // Get branch name
            $process = new Process([
                'git',
                'rev-parse',
                '--abbrev-ref',
                'HEAD',
            ], $this->repositoryPath);
            $process->run();
            if ($process->isSuccessful()) {
                $gitInfo['branch'] = trim($process->getOutput());
            }

            // Get latest commit info
            $process = new Process([
                'git',
                'log',
                '-1',
                '--pretty=format:%H|%an|%s',
            ], $this->repositoryPath);
            $process->run();
            if ($process->isSuccessful()) {
                [
                    $hash,
                    $author,
                    $message,
                ] = explode('|', trim($process->getOutput()));
                $gitInfo['commit'] = [
                    'hash' => $hash,
                    'author' => $author,
                    'message' => $message,
                ];
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
        } catch (ProcessFailedException $e) {
            if ($this->output) {
                $this->output->writeln('<error>Error while getting git info: '.$e->getMessage().'</error>');
            }
        }

        return $gitInfo;
    }

    public function formatRepositoryInfo(string $format = 'text'): string
    {
        $gitInfo = $this->getGitInfo();
        if (empty($gitInfo['branch'])) {
            return '';
        }

        return match ($format) {
            'json' => $this->formatInfoAsJson($gitInfo),
            'xml' => $this->formatInfoAsXml($gitInfo),
            default => $this->formatInfoAsText($gitInfo),
        };
    }

    private function formatInfoAsText(array $gitInfo): string
    {
        $info = "\nRepository Information:\n";
        $info .= "---------------------\n";
        $info .= "Branch: ".$gitInfo['branch']."\n";

        if (! empty($gitInfo['commit'])) {
            $info .= "Commit: ".$gitInfo['commit']['hash']."\n";
            $info .= "Author: ".$gitInfo['commit']['author']."\n";
            $info .= "Message: ".$gitInfo['commit']['message']."\n";
        }

        if (! empty($gitInfo['remotes'])) {
            $info .= "Remotes:\n";
            foreach ($gitInfo['remotes'] as $name => $urls) {
                $info .= "  - ".$name.": ".$urls['fetch']."\n";
            }
        }

        return $info."\n";
    }

    private function formatInfoAsJson(array $gitInfo): string
    {
        return json_encode([
                'repository' => [
                    'branch' => $gitInfo['branch'],
                    'commit' => $gitInfo['commit'],
                    'remotes' => $gitInfo['remotes'],
                ],
            ], JSON_PRETTY_PRINT)."\n";
    }

    private function formatInfoAsXml(array $gitInfo): string
    {
        $info = "\n<repository>";
        $info .= "\n  <branch>".htmlspecialchars($gitInfo['branch'])."</branch>";

        if (! empty($gitInfo['commit'])) {
            $info .= "\n  <commit>";
            $info .= "\n    <hash>".htmlspecialchars($gitInfo['commit']['hash'])."</hash>";
            $info .= "\n    <author>".htmlspecialchars($gitInfo['commit']['author'])."</author>";
            $info .= "\n    <message>".htmlspecialchars($gitInfo['commit']['message'])."</message>";
            $info .= "\n  </commit>";
        }

        if (! empty($gitInfo['remotes'])) {
            $info .= "\n  <remotes>";
            foreach ($gitInfo['remotes'] as $name => $urls) {
                $info .= "\n    <remote>";
                $info .= "\n      <name>".htmlspecialchars($name)."</name>";
                $info .= "\n      <url>".htmlspecialchars($urls['fetch'])."</url>";
                $info .= "\n    </remote>";
            }
            $info .= "\n  </remotes>";
        }

        $info .= "\n</repository>\n";

        return $info;
    }
}
