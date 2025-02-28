<?php

namespace Vangelis\RepoPHP\Services;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Vangelis\RepoPHP\Exceptions\GitRepositoryException;

class GitRepositoryService
{
    private string $tempDir;
    private ?OutputInterface $output;

    public function __construct(?OutputInterface $output = null)
    {
        $this->output = $output;
        $this->tempDir = '';
    }

    public function cloneRepository(string $repositoryUrl, ?string $branch = null): string
    {
        $this->tempDir = sys_get_temp_dir() . '/repophp-' . uniqid();

        if ($this->output) {
            $this->output->writeln("<info>🔄 Clone repository from: {$repositoryUrl}</info>");
            if ($branch) {
                $this->output->writeln("<info>🌿 Using branch: {$branch}</info>");
            }
        }

        try {
            if (! mkdir($this->tempDir, 0777, true) && ! is_dir($this->tempDir)) {
                throw new GitRepositoryException("Error creating temporary folder: {$this->tempDir}");
            }

            $command = ['git', 'clone'];

            $command[] = '--depth=1';

            if ($branch) {
                $command[] = '--branch';
                $command[] = $branch;
            }

            $command[] = $repositoryUrl;
            $command[] = $this->tempDir;

            $process = new Process($command);
            $process->setTimeout(300); 
            $process->run(function ($type, $buffer): void {
                if ($this->output) {
                    if (Process::ERR === $type) {
                        $this->output->write("<comment>{$buffer}</comment>");
                    } else {
                        $this->output->write("<info>{$buffer}</info>");
                    }
                }
            });

            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return $this->tempDir;
        } catch (\Exception $e) {
            $this->cleanup();

            throw new GitRepositoryException('Repository could not be cloned: ' . $e->getMessage(), 0, $e);
        }
    }

    public function cleanup(): void
    {
        if (! empty($this->tempDir) && is_dir($this->tempDir)) {
            $this->deleteDirectory($this->tempDir);
            if ($this->output) {
                $this->output->writeln("<info>🧹 Temporary repository folder was removed.</info>");
            }
        }
    }

    private function deleteDirectory(string $dir): bool
    {
        if (! file_exists($dir)) {
            return true;
        }

        if (! is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (! $this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}
