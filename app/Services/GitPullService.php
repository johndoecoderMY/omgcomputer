<?php

namespace App\Services;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class GitPullService
{
    public function pull(): void
    {
        $repository = $this->runGit(['git', 'rev-parse', '--is-inside-work-tree']);

        if (! $repository->successful() || trim($repository->output()) !== 'true') {
            throw new RuntimeException('Git is unavailable or the application directory is not a Git checkout.');
        }

        $status = $this->runGit(['git', 'status', '--porcelain', '--untracked-files=no']);

        if (! $status->successful()) {
            throw new RuntimeException('Could not check the Git working tree. Verify the server Git setup.');
        }

        if (trim($status->output()) !== '') {
            throw new RuntimeException('Git pull stopped because tracked files have local changes. Commit or revert them before updating.');
        }

        $pull = $this->runGit(['git', 'pull', '--ff-only']);

        if (! $pull->successful()) {
            Log::warning('Admin Git pull failed.', [
                'exit_code' => $pull->exitCode(),
            ]);

            throw new RuntimeException('Git pull failed. Check the configured remote, branch, credentials, and server Git access.');
        }
    }

    /**
     * @param  list<string>  $command
     */
    private function runGit(array $command): ProcessResult
    {
        return Process::path(base_path())->timeout(120)->run($command);
    }
}
