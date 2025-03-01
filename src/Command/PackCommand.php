<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Vangelis\RepoPHP\Config\ConfigLoader;
use Vangelis\RepoPHP\Exceptions\GitRepositoryException;
use Vangelis\RepoPHP\RepoPHP;
use Vangelis\RepoPHP\Services\GitRepositoryService;

class PackCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('pack')
            ->setDescription('Pack a repository into a single AI-friendly file')
            ->addArgument(
                'output',
                InputArgument::REQUIRED,
                'Path to the output file'
            )
            ->addArgument(
                'repository',
                InputArgument::REQUIRED,
                'Path to the repository (local path or remote URL if --remote flag is set)'
            )
            ->addOption(
                'remote',
                'rem',
                InputOption::VALUE_NONE,
                'Treat the repository argument as a remote Git repository URL'
            )
            ->addOption(
                'branch',
                'bra',
                InputOption::VALUE_REQUIRED,
                'Branch to checkout when cloning a remote repository',
                'main'
            )
            ->addOption(
                'format',
                'fmt',
                InputOption::VALUE_REQUIRED,
                'Output format (plain, markdown, json, xml)',
                'plain'
            )
            ->addOption(
                'encoding',
                'enc',
                InputOption::VALUE_REQUIRED,
                'Token encoding (cl100k_base, p50k_base, r50k_base, p50k_edit)',
                'p50k_base'
            )
            ->addOption(
                'exclude',
                'exc',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Additional patterns to exclude files'
            )
            ->addOption(
                'no-gitignore',
                'nog',
                InputOption::VALUE_NONE,
                'Do not respect .gitignore files'
            )
            ->addOption(
                'compress',
                'com',
                InputOption::VALUE_NONE,
                'Remove comments and empty lines from files'
            )
            ->addOption(
                'max-tokens',
                'mxt',
                InputOption::VALUE_REQUIRED,
                'Maximum tokens per output file (splits into multiple files when exceeded)',
                0 // 0 means no limit
            )
            ->addOption(
                'incremental',
                'inc',
                InputOption::VALUE_NONE,
                'Create an incremental diff based on changes since last pack'
            )
            ->addOption(
                'base-file',
                'base',
                InputOption::VALUE_REQUIRED,
                'The base file to compare against for incremental packing (required for incremental mode)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Load configuration from file if exists
        try {
            $fileConfig = ConfigLoader::loadConfig();
            if (!empty($fileConfig)) {
                $output->writeln('<info>Using configuration from file</info>');
            }
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        // Get repository argument or from config file
        $repository = $input->getArgument('repository') ?? $fileConfig['repository'] ?? null;
        if (!$repository) {
            $output->writeln('<error>Repository path is required</error>');
            return Command::FAILURE;
        }

        // Apply config file defaults but let CLI options override them
        $isRemote = $input->getOption('remote') ?? $fileConfig['remote'] ?? false;
        $outputPath = $input->getArgument('output') ?? $fileConfig['output'] ?? null;
        if (!$outputPath) {
            $output->writeln('<error>Output path is required</error>');
            return Command::FAILURE;
        }

        $branch = $input->getOption('branch') ?? $fileConfig['branch'] ?? 'main';
        $format = $input->getOption('format') ?? $fileConfig['format'] ?? 'plain';
        $encoding = $input->getOption('encoding') ?? $fileConfig['encoding'] ?? 'p50k_base';
        $excludePatterns = $input->getOption('exclude') ?: ($fileConfig['exclude'] ?? []);
        $respectGitignore = !($input->getOption('no-gitignore') ?? $fileConfig['no-gitignore'] ?? false);
        $compress = $input->getOption('compress') ?? $fileConfig['compress'] ?? false;
        $maxTokens = (int)($input->getOption('max-tokens') ?? $fileConfig['max-tokens'] ?? 0);
        $incrementalMode = $input->getOption('incremental') ?? $fileConfig['incremental'] ?? false;
        $baseFilePath = $input->getOption('base-file') ?? $fileConfig['base-file'] ?? null;

        // Continue with the existing execution logic from here, using the merged configs
        $gitService = null;
        $tempDir = null;
        $repositoryPath = null;

        try {
            // Handle repository based on whether it's remote or local
            if ($isRemote) {
                $gitService = new GitRepositoryService($output);

                try {
                    $repositoryPath = $gitService->cloneRepository($repository, $branch);
                    $output->writeln("<info>Repository cloned to: {$repositoryPath}</info>");
                    $tempDir = $repositoryPath;
                } catch (GitRepositoryException $e) {
                    $output->writeln('<error>' . $e->getMessage() . '</error>');

                    return Command::FAILURE;
                }
            } else {
                $repositoryPath = $repository;
            }

            // Validate incremental mode requirements
            if ($incrementalMode && ! $baseFilePath) {
                $output->writeln('<error>Base file is required for incremental packing. Use --base-file option.</error>');

                return Command::FAILURE;
            }

            if ($incrementalMode && ! file_exists($baseFilePath)) {
                $output->writeln('<error>Base file does not exist: ' . $baseFilePath . '</error>');

                return Command::FAILURE;
            }

            // Check for output file overwrite
            if (file_exists($outputPath)) {
                /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
                $helper = $this->getHelper('question');

                if ($incrementalMode) {
                    // For incremental mode, ask if user wants to create a diff or override
                    $question = new ChoiceQuestion(
                        "File '$outputPath' already exists. What would you like to do?",
                        ['diff' => 'Create a diff file', 'overwrite' => 'Overwrite existing file', 'cancel' => 'Cancel operation'],
                        'diff'
                    );

                    $answer = $helper->ask($input, $output, $question);

                    if ($answer === 'cancel') {
                        $output->writeln('<info>Operation cancelled.</info>');

                        return Command::SUCCESS;
                    } elseif ($answer === 'diff') {
                        // Already handled by the RepoPHP class
                    }
                } else {
                    // Existing overwrite handling for non-incremental mode
                    $question = new ConfirmationQuestion(
                        "File '$outputPath' already exists. Do you want to overwrite it? (y/N) ",
                        false
                    );

                    if (! $helper->ask($input, $output, $question)) {
                        // User chose not to overwrite, create a new filename with timestamp
                        $pathInfo = pathinfo($outputPath);
                        $newFilename = sprintf(
                            '%s/%s_%s.%s',
                            $pathInfo['dirname'],
                            $pathInfo['filename'],
                            date('Y-m-d_His'),
                            $pathInfo['extension']
                        );

                        $output->writeln("<info>Creating file with new name: $newFilename</info>");
                        $outputPath = $newFilename;
                    }
                }
            }

            $repoPHP = new RepoPHP(
                $repositoryPath,
                $outputPath,
                $format,
                $excludePatterns,
                $respectGitignore,
                $output,
                $encoding,
                $compress,
                null,
                $maxTokens,
                $incrementalMode,
                $baseFilePath
            );

            $repoPHP->pack();

            $output->writeln('<info>' . ($incrementalMode ? 'Incremental diff' : 'Repository') . ' packed successfully.</info>');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            if ($gitService && $tempDir) {
                $gitService->cleanup();
            }

            return Command::FAILURE;
        }
    }
}
