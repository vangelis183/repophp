<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Vangelis\RepoPHP\Command\PackCommand;

beforeEach(function () {
    $this->application = new Application();
    $this->application->add(new PackCommand());
    $this->command = $this->application->find('pack');
});

afterEach(function () {
    // Remove any temporary output file created during tests.
    if (isset($this->tmpOutput) && file_exists($this->tmpOutput)) {
        unlink($this->tmpOutput);
    }
    // Remove any new file created during the overwrite prompt test.
    if (isset($this->newFilename) && file_exists($this->newFilename)) {
        unlink($this->newFilename);
    }
    // Remove temporary repository directory if exists.
    if (isset($this->tmpRepo) && is_dir($this->tmpRepo)) {
        rmdir($this->tmpRepo);
    }
});

test('it fails if repository argument is missing', function () {
    $this->tmpOutput = sys_get_temp_dir() . '/tmp_output_' . uniqid() . '.txt';
    $commandTester = new CommandTester($this->command);
    // Missing the repository argument should throw an exception.
    $this->expectException(RuntimeException::class);
    $commandTester->execute([
        'output' => $this->tmpOutput,
    ]);
});

test('it fails if output argument is missing', function () {
    $this->tmpRepo = sys_get_temp_dir() . '/tmp_repo_' . uniqid();
    mkdir($this->tmpRepo);
    $commandTester = new CommandTester($this->command);
    // Missing the output argument should throw an exception.
    $this->expectException(RuntimeException::class);
    $commandTester->execute([
        'repository' => $this->tmpRepo,
    ]);
});

test('it packs repository successfully with minimal required arguments', function () {
    $this->tmpRepo = sys_get_temp_dir() . '/tmp_repo_' . uniqid();
    mkdir($this->tmpRepo);
    $this->tmpOutput = sys_get_temp_dir() . '/tmp_output_' . uniqid() . '.txt';

    $commandTester = new CommandTester($this->command);
    $exitCode = $commandTester->execute([
        'repository' => $this->tmpRepo,
        'output' => $this->tmpOutput,
    ]);

    expect($exitCode)->toBe(Command::SUCCESS);
    expect($commandTester->getDisplay())->toContain('Repository packed successfully.');
});

test('it packs repository successfully when output file exists and user declines to overwrite', function () {
    $this->tmpRepo = sys_get_temp_dir() . '/tmp_repo_' . uniqid();
    mkdir($this->tmpRepo);
    // Create the output file so the command prompts for overwrite.
    $this->tmpOutput = sys_get_temp_dir() . '/tmp_output_' . uniqid() . '.txt';
    file_put_contents($this->tmpOutput, 'existing content');

    $commandTester = new CommandTester($this->command);
    // Simulate user input: "n" (declining the overwrite)
    $commandTester->setInputs(['n']);

    $exitCode = $commandTester->execute([
        'repository' => $this->tmpRepo,
        'output' => $this->tmpOutput,
    ]);

    expect($exitCode)->toBe(Command::SUCCESS);
    expect($commandTester->getDisplay())->toContain('Creating file with new name:');
    expect($commandTester->getDisplay())->toContain('Repository packed successfully.');
});

test('it packs repository with the custom --format option', function () {
    $this->tmpRepo = sys_get_temp_dir() . '/tmp_repo_' . uniqid();
    mkdir($this->tmpRepo);
    // Use an output file that does not exist.
    $this->tmpOutput = sys_get_temp_dir() . '/tmp_output_' . uniqid() . '.json';

    $commandTester = new CommandTester($this->command);
    $exitCode = $commandTester->execute([
        'repository' => $this->tmpRepo,
        'output' => $this->tmpOutput,
        '--format' => 'json',
    ]);

    expect($exitCode)->toBe(Command::SUCCESS);
    expect($commandTester->getDisplay())->toContain('Repository packed successfully.');
});

test('it packs repository with the custom --exclude option (single and multiple patterns)', function () {
    $this->tmpRepo = sys_get_temp_dir() . '/tmp_repo_' . uniqid();
    mkdir($this->tmpRepo);
    // Prepare an output file with a different extension.
    $this->tmpOutput = sys_get_temp_dir() . '/tmp_output_' . uniqid() . '.txt';

    $commandTester = new CommandTester($this->command);
    // Test with a single exclusion pattern
    $exitCodeSingle = $commandTester->execute([
        'repository' => $this->tmpRepo,
        'output' => $this->tmpOutput,
        '--exclude' => ['*.log'],
    ]);

    expect($exitCodeSingle)->toBe(Command::SUCCESS);
    expect($commandTester->getDisplay())->toContain('Repository packed successfully.');

    // Test with multiple exclusion patterns
    $this->tmpOutput = sys_get_temp_dir() . '/tmp_output_' . uniqid() . '.txt';
    $commandTester = new CommandTester($this->command);
    $exitCodeMultiple = $commandTester->execute([
        'repository' => $this->tmpRepo,
        'output' => $this->tmpOutput,
        '--exclude' => ['*.log', '*.tmp'],
    ]);

    expect($exitCodeMultiple)->toBe(Command::SUCCESS);
    expect($commandTester->getDisplay())->toContain('Repository packed successfully.');
});

test('it packs repository with the --no-gitignore option', function () {
    $this->tmpRepo = sys_get_temp_dir() . '/tmp_repo_' . uniqid();
    mkdir($this->tmpRepo);
    // Use a unique output file name.
    $this->tmpOutput = sys_get_temp_dir() . '/tmp_output_' . uniqid() . '.xml';

    $commandTester = new CommandTester($this->command);
    $exitCode = $commandTester->execute([
        'repository' => $this->tmpRepo,
        'output' => $this->tmpOutput,
        '--no-gitignore' => true,
    ]);

    expect($exitCode)->toBe(Command::SUCCESS);
    expect($commandTester->getDisplay())->toContain('Repository packed successfully.');
});
