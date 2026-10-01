<?php

namespace Tests\Feature;

use App\Services\GitPushService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class GitPushServiceTest extends TestCase
{
    private $app;

    private string $directory;

    private string $repository;

    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(Kernel::class)->bootstrap();
        $this->directory = sys_get_temp_dir().'/git-push-test-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        $this->repository = $this->directory.'/work';
        $this->command(['git', 'init', '--bare', $this->directory.'/remote.git']);
        $this->command(['git', 'init', '-b', 'test-app', $this->repository]);
        $this->command(['git', '-C', $this->repository, 'config', 'user.name', 'Test']);
        $this->command(['git', '-C', $this->repository, 'config', 'user.email', 'test@example.invalid']);
        $this->command(['git', '-C', $this->repository, 'remote', 'add', 'origin', $this->directory.'/remote.git']);
        file_put_contents($this->repository.'/initial.txt', 'initial');
        $this->command(['git', '-C', $this->repository, 'add', '.']);
        $this->command(['git', '-C', $this->repository, 'commit', '-m', 'initial']);
        $this->app->setBasePath($this->repository);
    }

    private function command(array $arguments): string
    {
        $process = new Process($arguments);
        $process->mustRun();

        return trim($process->getOutput());
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->directory);
        $this->app->flush();
        restore_error_handler();
        restore_exception_handler();
    }

    public function test_check_does_not_commit_or_publish_and_push_uses_current_branch(): void
    {
        file_put_contents($this->repository.'/change.txt', 'change');
        $service = new GitPushService;
        $service->checkConnection();
        self::assertSame('test-app', $service->branch);
        self::assertSame('1', $this->command(['git', '-C', $this->repository, 'rev-list', '--count', 'HEAD']));
        self::assertSame('', $this->command(['git', '--git-dir='.$this->directory.'/remote.git', 'branch']));
        $service->push('literal $(touch unexpected)');
        $local = $this->command(['git', '-C', $this->repository, 'rev-parse', 'HEAD']);
        self::assertSame($local, $this->command(['git', '--git-dir='.$this->directory.'/remote.git', 'rev-parse', 'refs/heads/test-app']));
        self::assertFileDoesNotExist($this->repository.'/unexpected');
    }

    public function test_failed_connection_does_not_stage_or_commit_changes(): void
    {
        $this->command(['git', '-C', $this->repository, 'remote', 'set-url', 'origin', $this->directory.'/missing.git']);
        file_put_contents($this->repository.'/change.txt', 'change');
        try {
            (new GitPushService)->push('must not commit');
            self::fail('Expected connection failure');
        } catch (\RuntimeException) {
            self::assertSame('1', $this->command(['git', '-C', $this->repository, 'rev-list', '--count', 'HEAD']));
            self::assertSame('', $this->command(['git', '-C', $this->repository, 'diff', '--cached', '--name-only']));
        }
    }
}
