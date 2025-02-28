<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Vangelis\RepoPHP\Exceptions\GitRepositoryException;
use Vangelis\RepoPHP\RepoPHP;
use Vangelis\RepoPHP\Services\GitRepositoryService;

class PackCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('pack')->setDescription('Pack a local repository directory into a single AI-friendly file')->addArgument(
            'repository',
            InputArgument::REQUIRED,
            'Path to the repository directory'
        )->addArgument(
            'output',
            InputArgument::REQUIRED,
            'Path to the output file'
        )->addOption(
            'remote',
            'remote',
            InputOption::VALUE_OPTIONAL,
            'URL of the remote Git repository to clone and pack'
        )->addOption(
            'branch',
            'branch',
            InputOption::VALUE_OPTIONAL,
            'Branch to checkout when cloning a remote repository',
            'main'
        )->addOption(
            'format',
            'format',
            InputOption::VALUE_REQUIRED,
            'Output format (plain, markdown, json, xml)',
            'plain'
        ) ->addOption(
            'encoding',
            'enc',
            InputOption::VALUE_REQUIRED,
            'Token encoding (cl100k_base, p50k_base, r50k_base, p50k_edit)',
            'p50k_base'
        )->addOption(
            'exclude',
            'exclude',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Additional patterns to exclude files'
        )->addOption(
            'no-gitignore',
            'noignore',
            InputOption::VALUE_NONE,
            'Do not respect .gitignore files'
        )->addOption(
            'compress',
            'compress',
            InputOption::VALUE_NONE,
            'Remove comments and empty lines from files'
        );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repositoryPath = $input->getArgument('repository');
        $remoteUrl = $input->getOption('remote');
        $outputPath = $input->getArgument('output');
        $branch = $input->getOption('branch');
        $gitService = null;
        $tempDir = null;

        if ($remoteUrl) {
            $gitService = new GitRepositoryService($output);

            try {
                $repositoryPath = $gitService->cloneRepository($remoteUrl, $branch);
                $output->writeln("<info>Repository wurde geklont nach: {$repositoryPath}</info>");
                $tempDir = $repositoryPath;
            } catch (GitRepositoryException $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');

                return Command::FAILURE;
            }
        } elseif (! $repositoryPath) {
            $output->writeln('<error>Sie müssen entweder einen lokalen Repository-Pfad oder eine Remote-Repository-URL mit --remote angeben.</error>');

            return Command::FAILURE;
        }

        // Add warning if file exists
        if (file_exists($outputPath)) {
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
            $helper = $this->getHelper('question');
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

        try {
            $repoPHP = new RepoPHP(
                $repositoryPath,
                $outputPath,
                $input->getOption('format'),
                $input->getOption('exclude'),
                ! $input->getOption('no-gitignore'),
                $output,
                $input->getOption('encoding'),
                $input->getOption('compress')
            );

            $repoPHP->pack();

            $output->writeln('<info>Repository packed successfully.</info>');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            if ($gitService && $tempDir) {
                $gitService->cleanup();
            }

            return Command::FAILURE;
        }
    }
}
