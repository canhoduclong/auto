<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class GitPushService
{
    public array $logs = [];

    public string $branch = '';

    public array $changedFiles = [];

    private array $environment = ['GIT_TERMINAL_PROMPT' => '0'];

    public function publicKey(): ?string
    {
        $path = storage_path('app/private/github-push/id_ed25519.pub');

        return is_readable($path) ? trim(file_get_contents($path)) : null;
    }

    public function checkConnection(): void
    {
        $this->branch = trim($this->git(['symbolic-ref', '--quiet', '--short', 'HEAD']));
        if ($this->branch === '') {
            throw new \RuntimeException('Repository chưa có nhánh làm việc.');
        }
        $this->logs[] = 'Repository: '.base_path();
        $this->logs[] = 'Branch: '.$this->branch;
        $remote = trim($this->git(['remote', 'get-url', '--push', 'origin']));
        if (preg_match('~^(git@github\.com:|ssh://git@github\.com/)~', $remote)) {
            $this->prepareSsh();
        }
        // Check the receive endpoint and fast-forward policy without updating GitHub.
        $this->git(['push', '--dry-run', '--porcelain', 'origin', 'HEAD:refs/heads/'.$this->branch]);
        $this->logs[] = 'Kết nối và quyền push hợp lệ (dry-run, chưa đẩy code).';
    }

    public function push(string $message): void
    {
        $this->checkConnection();
        $status = $this->git(['status', '--short']);
        $this->changedFiles = array_values(array_filter(explode("\n", trim($status))));
        $this->git(['add', '--all']);
        $diff = $this->git(['diff', '--cached', '--name-only']);
        if (trim($diff) !== '') {
            $this->git(['commit', '-m', $message]);
        } else {
            $this->logs[] = 'Không có thay đổi mới để commit.';
        }
        $this->git(['push', '--porcelain', 'origin', 'HEAD:refs/heads/'.$this->branch]);
    }

    private function prepareSsh(): void
    {
        $directory = storage_path('app/private/github-push');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Không tạo được thư mục SSH riêng cho tài khoản chạy web.');
        }
        if (! @chmod($directory, 0700)) {
            throw new \RuntimeException('Không đặt được quyền 0700 cho thư mục SSH. Kiểm tra chủ sở hữu thư mục của tài khoản chạy web.');
        }
        $identity = $directory.'/id_ed25519';
        if (! is_file($identity)) {
            $process = new Process(['ssh-keygen', '-q', '-t', 'ed25519', '-N', '', '-C', 'app.com-web-push', '-f', $identity]);
            $process->setTimeout(15)->mustRun();
            chmod($identity, 0600);
            $this->logs[] = 'Đã tạo SSH key riêng. Thêm public key hiển thị trong cài đặt vào Deploy keys của repository và bật Allow write access.';
        }
        // Repair existing keys too: deployment tools may have widened their permissions.
        if (! @chmod($identity, 0600)) {
            throw new \RuntimeException('Không đặt được quyền 0600 cho private key SSH. Kiểm tra chủ sở hữu file của tài khoản chạy web.');
        }
        clearstatcache(true, $identity);
        if ((fileperms($identity) & 0777) !== 0600 || ! is_readable($identity)) {
            throw new \RuntimeException('Private key SSH phải có quyền 0600 và tài khoản chạy web phải đọc được.');
        }
        $knownHosts = $directory.'/known_hosts';
        if (! is_file($knownHosts)) {
            // Obtain GitHub host keys via verified HTTPS; never disable SSH host verification.
            $metadata = Http::timeout(15)->acceptJson()->get('https://api.github.com/meta')->throw()->json();
            $keys = collect($metadata['ssh_keys'] ?? [])->filter(fn ($key) => is_string($key)
                && preg_match('/^ssh-ed25519 [A-Za-z0-9+\/=]+$/D', $key));
            if ($keys->isEmpty()) {
                throw new \RuntimeException('Không lấy được host key hợp lệ từ GitHub.');
            }
            if (file_put_contents($knownHosts, $keys->map(fn ($key) => 'github.com '.$key)->implode("\n")."\n", LOCK_EX) === false) {
                throw new \RuntimeException('Không ghi được known_hosts cho GitHub.');
            }
            chmod($knownHosts, 0600);
        }
        $this->environment['GIT_SSH_COMMAND'] = 'ssh -F /dev/null -o BatchMode=yes -o IdentitiesOnly=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -o UserKnownHostsFile='
            .escapeshellarg($knownHosts).' -i '.escapeshellarg($identity);
    }

    private function git(array $arguments): string
    {
        $process = new Process(array_merge(['git', '-c', 'safe.directory='.base_path()], $arguments), base_path(), $this->environment);
        $process->setTimeout(45);
        $process->run();
        $output = trim($process->getOutput()."\n".$process->getErrorOutput());
        if ($output !== '') {
            $this->logs[] = $output;
        }
        if (! $process->isSuccessful()) {
            $hint = str_contains($output, 'UNPROTECTED PRIVATE KEY FILE') || str_contains($output, 'bad permissions')
                ? 'Quyền private key SSH không hợp lệ. File cần quyền 0600 và thuộc tài khoản chạy web.'
                : (str_contains($output, 'Permission denied (publickey)')
                ? 'SSH key của web chưa được GitHub cho phép. Thêm public key bên dưới vào Deploy keys và bật Allow write access.'
                : (str_contains($output, 'Host key verification failed')
                    ? 'Xác minh host key GitHub thất bại. Cần kiểm tra known_hosts của tài khoản chạy web.'
                    : 'Git thất bại ở bước '.$arguments[0].'. Xem nhật ký bên dưới.'));
            throw new \RuntimeException($hint);
        }

        return $output;
    }
}
