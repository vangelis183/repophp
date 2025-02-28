<?php

namespace Vangelis\RepoPHP\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Vangelis\RepoPHP\Exceptions\GitRepositoryException;
use Vangelis\RepoPHP\Services\GitRepositoryService;

class GitRepositoryServiceTest extends TestCase
{
    private GitRepositoryService $gitService;
    private BufferedOutput $output;

    protected function setUp(): void
    {
        $this->output = new BufferedOutput();
        $this->gitService = new GitRepositoryService($this->output);
    }

    public function testCloneRepositoryWithInvalidUrl(): void
    {
        $this->expectException(GitRepositoryException::class);
        
        $this->gitService->cloneRepository('https://example.com/invalid-repo.git');
    }

    public function testCloneRealRepository(): void
    {
        if (!$this->isInternetAvailable()) {
            $this->markTestSkipped('Keine Internetverbindung verfügbar');
        }

        try {
            $repoDir = $this->gitService->cloneRepository('https://github.com/vangelis183/repophp.git');

            $this->assertDirectoryExists($repoDir);
            $this->assertFileExists($repoDir . '/README.md');
            $this->assertFileExists($repoDir . '/composer.json');
            
            $output = $this->output->fetch();
            $this->assertStringContainsString('Klone Repository', $output);

            $branchDir = $this->gitService->cloneRepository('https://github.com/vangelis183/repophp.git', 'main');
            $this->assertDirectoryExists($branchDir);
            
        } finally {
            $this->gitService->cleanup();
        }
    }


    private function isInternetAvailable(): bool
    {
        $connected = @fsockopen('github.com', 443);
        if ($connected) {
            fclose($connected);
            return true;
        }
        return false;
    }

    public function testCleanup(): void
    {
        $this->gitService->cleanup();
        $this->assertTrue(true);
    }
    

}