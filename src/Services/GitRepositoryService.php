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
            $this->output->writeln("<info>🔄 Klone Repository von: {$repositoryUrl}</info>");
            if ($branch) {
                $this->output->writeln("<info>🌿 Verwende Branch: {$branch}</info>");
            }
        }

        try {
            // Temporäres Verzeichnis erstellen
            if (!mkdir($this->tempDir, 0777, true) && !is_dir($this->tempDir)) {
                throw new GitRepositoryException("Fehler beim Erstellen des temporären Verzeichnisses: {$this->tempDir}");
            }

            // Clone-Befehl vorbereiten
            $command = ['git', 'clone'];
            
            // Tiefe beschränken für schnellere Klone
            $command[] = '--depth=1';
            
            // Branch-Parameter hinzufügen, falls angegeben
            if ($branch) {
                $command[] = '--branch';
                $command[] = $branch;
            }
            
            // Repository-URL und Zielverzeichnis
            $command[] = $repositoryUrl;
            $command[] = $this->tempDir;

            $process = new Process($command);
            $process->setTimeout(300); // 5 Minuten Timeout
            $process->run(function ($type, $buffer): void {
                if ($this->output) {
                    if (Process::ERR === $type) {
                        $this->output->write("<comment>{$buffer}</comment>");
                    } else {
                        $this->output->write("<info>{$buffer}</info>");
                    }
                }
            });

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return $this->tempDir;
        } catch (\Exception $e) {
            $this->cleanup();
            throw new GitRepositoryException('Repository konnte nicht geklont werden: ' . $e->getMessage(), 0, $e);
        }
    }

    public function cleanup(): void
    {
        if (!empty($this->tempDir) && is_dir($this->tempDir)) {
            $this->deleteDirectory($this->tempDir);
            if ($this->output) {
                $this->output->writeln("<info>🧹 Temporäres Repository-Verzeichnis wurde bereinigt.</info>");
            }
        }
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}