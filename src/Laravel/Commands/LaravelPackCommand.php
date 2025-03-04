<?php

namespace Vangelis\RepoPHP\Laravel\Commands;

use Illuminate\Console\Command;
use Vangelis\RepoPHP\RepoPHP;
use Vangelis\RepoPHP\Config\RepoPHPConfig;
use Vangelis\RepoPHP\Services\GitRepositoryService;

class LaravelPackCommand extends Command
{
    protected $signature = 'repophp:pack
                            {output? : Path to the output file}
                            {repository? : Path to the repository (local path or remote URL if --remote flag is set)}
                            {--remote : Treat the repository argument as a remote Git repository URL}
                            {--branch=main : Branch to checkout when cloning a remote repository}
                            {--format=plain : Output format (plain, markdown, json, xml)}
                            {--encoding=p50k_base : Token encoding (cl100k_base, p50k_base, r50k_base, p50k_edit)}
                            {--exclude=* : Additional patterns to exclude files}
                            {--no-gitignore : Do not respect .gitignore files}
                            {--compress : Remove comments and empty lines from files}
                            {--max-tokens=0 : Maximum tokens per output file (splits into multiple files when exceeded)}
                            {--incremental : Create an incremental diff based on changes since last pack}
                            {--base-file= : The base file to compare against for incremental packing}';

    protected $description = 'Pack a repository into a single AI-friendly file';

    public function handle()
    {
        // Get the repository path from arguments or config
        $repository = $this->argument('repository') ?? config('repophp.repository_path', base_path());
        $outputPath = $this->argument('output') ?? config('repophp.output_path');
        $format = $this->option('format') ?? config('repophp.format');
        $encoding = $this->option('encoding') ?? config('repophp.encoding');
        $isRemote = $this->option('remote');
        $branch = $this->option('branch');
        $excludePatterns = $this->option('exclude') ?: config('repophp.exclude_patterns', []);
        $respectGitignore = !$this->option('no-gitignore');
        $compress = $this->option('compress') ?? config('repophp.compress', false);
        $maxTokens = (int)($this->option('max-tokens') ?? config('repophp.max_tokens_per_file', 0));
        $incremental = $this->option('incremental') ?? config('repophp.incremental', false);
        $baseFilePath = $this->option('base-file') ?? config('repophp.base_file');

        $gitService = null;
        $tempDir = null;
        $repositoryPath = null;

        try {
            // Handle repository based on whether it's remote or local
            if ($isRemote) {
                $gitService = new GitRepositoryService($this->output);

                $repositoryPath = $gitService->cloneRepository($repository, $branch);
                $this->info("Repository cloned to: {$repositoryPath}");
                $tempDir = $repositoryPath;
            } else {
                $repositoryPath = $repository;
            }

            // Validate incremental mode requirements
            if ($incremental && !$baseFilePath) {
                $this->error('Base file is required for incremental packing. Use --base-file option.');
                return 1;
            }

            if ($incremental && !file_exists($baseFilePath)) {
                $this->error('Base file does not exist: ' . $baseFilePath);
                return 1;
            }

            // Create storage directory if needed
            $outputDir = dirname($outputPath);
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Check for output file overwrite
            if (file_exists($outputPath)) {
                if ($incremental) {
                    $choice = $this->choice(
                        "File '$outputPath' already exists. What would you like to do?",
                        ['diff' => 'Create a diff file', 'overwrite' => 'Overwrite existing file', 'cancel' => 'Cancel operation'],
                        'diff'
                    );

                    if ($choice === 'cancel') {
                        $this->info('Operation cancelled.');
                        return 0;
                    }
                } else {
                    if (!$this->confirm("File '$outputPath' already exists. Do you want to overwrite it?", false)) {
                        // User chose not to overwrite, create a new filename with timestamp
                        $pathInfo = pathinfo($outputPath);
                        $newFilename = sprintf(
                            '%s/%s_%s.%s',
                            $pathInfo['dirname'],
                            $pathInfo['filename'],
                            date('Y-m-d_His'),
                            $pathInfo['extension'] ?? 'txt'
                        );

                        $this->info("Creating file with new name: $newFilename");
                        $outputPath = $newFilename;
                    }
                }
            }

            // Create RepoPHP instance
            $repoPHP = new RepoPHP(
                $repositoryPath,
                $outputPath,
                $format,
                $excludePatterns,
                $respectGitignore,
                $this->output,
                $encoding,
                $compress,
                null, // token counter path from default
                $maxTokens,
                $incremental,
                $baseFilePath
            );

            // Pack the repository
            $repoPHP->pack();

            $this->info('Repository packed successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());

            if ($gitService && $tempDir) {
                $gitService->cleanup();
            }

            return 1;
        }
    }
}
