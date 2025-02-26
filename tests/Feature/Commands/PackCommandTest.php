<?php

namespace Vangelis\RepoPHP\Tests\Feature\Commands;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Vangelis\RepoPHP\Command\PackCommand;

class PackCommandTest extends TestCase
{
    private Application $application;
    private Command $command;
    private ?string $tmpOutput = null;
    private ?string $tmpRepo = null;
    private ?string $newFilename = null;

    protected function setUp(): void
    {
        $this->application = new Application();
        $this->application->add(new PackCommand());
        $this->command = $this->application->find('pack');

        // Create vendor/bin directory if it doesn't exist
        $vendorBinDir = dirname(__DIR__, 3) . '/bin';
        if (!is_dir($vendorBinDir)) {
            mkdir($vendorBinDir, 0777, true);
        }

        // Create mock token counter binary
        $tokenCounterPath = $vendorBinDir . '/token-counter';
        file_put_contents($tokenCounterPath, '#!/bin/bash' . PHP_EOL . 'echo "10"');
        chmod($tokenCounterPath, 0755);
    }

    protected function tearDown(): void
    {
        if ($this->tmpOutput && file_exists($this->tmpOutput)) {
            unlink($this->tmpOutput);
        }

        if ($this->newFilename && file_exists($this->newFilename)) {
            unlink($this->newFilename);
        }

        if ($this->tmpRepo && is_dir($this->tmpRepo)) {
            rmdir($this->tmpRepo);
        }

        // Clean up token counter binary
        $tokenCounterPath = dirname(__DIR__, 3) . '/bin/token-counter';
        if (file_exists($tokenCounterPath)) {
            unlink($tokenCounterPath);
        }
    }

    public function testFailsIfRepositoryArgumentIsMissing(): void
    {
        $this->tmpOutput = sys_get_temp_dir() . '/tmp_output_' . uniqid() . '.txt';
        $commandTester = new CommandTester($this->command);

        $this->expectException(RuntimeException::class);
        $commandTester->execute([
            'output' => $this->tmpOutput,
        ]);
    }

    public function testFailsIfOutputArgumentIsMissing(): void
    {
        $this->tmpRepo = sys_get_temp_dir() . '/tmp_repo_' . uniqid();
        mkdir($this->tmpRepo);
        $commandTester = new CommandTester($this->command);

        $this->expectException(RuntimeException::class);
        $commandTester->execute([
            'repository' => $this->tmpRepo,
        ]);
    }

    public function testPacksRepositorySuccessfullyWithMinimalRequiredArguments(): void
    {
        $this->tmpRepo = sys_get_temp_dir() . '/tmp_repo_' . uniqid();
        mkdir($this->tmpRepo);
        $this->tmpOutput = sys_get_temp_dir() . '/tmp_output_' . uniqid() . '.txt';

        $commandTester = new CommandTester($this->command);
        $exitCode = $commandTester->execute([
            'repository' => $this->tmpRepo,
            'output' => $this->tmpOutput,
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Repository packed successfully.', $commandTester->getDisplay());
    }

    public function testPacksRepositoryWhenOutputFileExistsAndUserDeclinesOverwrite(): void
    {
        $this->tmpRepo = sys_get_temp_dir() . '/tmp_repo_' . uniqid();
        mkdir($this->tmpRepo);
        $this->tmpOutput = sys_get_temp_dir() . '/tmp_output_' . uniqid() . '.txt';
        file_put_contents($this->tmpOutput, 'existing content');

        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs(['n']);

        $exitCode = $commandTester->execute([
            'repository' => $this->tmpRepo,
            'output' => $this->tmpOutput,
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Creating file with new name:', $commandTester->getDisplay());
        $this->assertStringContainsString('Repository packed successfully.', $commandTester->getDisplay());
    }

    public function testPacksRepositoryWithCustomFormatOption(): void
    {
        $this->tmpRepo = sys_get_temp_dir() . '/tmp_repo_' . uniqid();
        mkdir($this->tmpRepo);
        $this->tmpOutput = sys_get_temp_dir() . '/tmp_output_' . uniqid() . '.json';

        $commandTester = new CommandTester($this->command);
        $exitCode = $commandTester->execute([
            'repository' => $this->tmpRepo,
            'output' => $this->tmpOutput,
            '--format' => 'json',
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Repository packed successfully.', $commandTester->getDisplay());
    }

    public function testPacksRepositoryWithExcludeOption(): void
    {
        $this->tmpRepo = sys_get_temp_dir() . '/tmp_repo_' . uniqid();
        mkdir($this->tmpRepo);
        $this->tmpOutput = sys_get_temp_dir() . '/tmp_output_' . uniqid() . '.txt';

        $commandTester = new CommandTester($this->command);

        // Test single pattern
        $exitCodeSingle = $commandTester->execute([
            'repository' => $this->tmpRepo,
            'output' => $this->tmpOutput,
            '--exclude' => ['*.log'],
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCodeSingle);
        $this->assertStringContainsString('Repository packed successfully.', $commandTester->getDisplay());

        // Test multiple patterns
        $this->tmpOutput = sys_get_temp_dir() . '/tmp_output_' . uniqid() . '.txt';
        $commandTester = new CommandTester($this->command);
        $exitCodeMultiple = $commandTester->execute([
            'repository' => $this->tmpRepo,
            'output' => $this->tmpOutput,
            '--exclude' => ['*.log', '*.tmp'],
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCodeMultiple);
        $this->assertStringContainsString('Repository packed successfully.', $commandTester->getDisplay());
    }

    public function testPacksRepositoryWithNoGitignoreOption(): void
    {
        $this->tmpRepo = sys_get_temp_dir() . '/tmp_repo_' . uniqid();
        mkdir($this->tmpRepo);
        $this->tmpOutput = sys_get_temp_dir() . '/tmp_output_' . uniqid() . '.xml';

        $commandTester = new CommandTester($this->command);
        $exitCode = $commandTester->execute([
            'repository' => $this->tmpRepo,
            'output' => $this->tmpOutput,
            '--no-gitignore' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Repository packed successfully.', $commandTester->getDisplay());
    }
}
