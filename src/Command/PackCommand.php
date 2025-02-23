<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vangelis\RepoPHP\RepoPHP;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
            'format',
            'format',
            InputOption::VALUE_REQUIRED,
            'Output format (plain, markdown, json)',
            'plain'
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
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repositoryPath = $input->getArgument('repository');
        $outputPath = $input->getArgument('output');

        // Add warning if file exists
        if (file_exists($outputPath)) {
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
                $output
            );

            $repoPHP->pack();

            $output->writeln('<info>Repository packed successfully.</info>');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
    }
}
