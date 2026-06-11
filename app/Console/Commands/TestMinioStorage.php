<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestMinioStorage extends Command
{
    protected $signature = 'storage:test-minio {--disk= : Disk name (default: filesystems.default)}';

    protected $description = 'Verify MinIO / S3 storage connectivity';

    public function handle(): int
    {
        $diskName = $this->option('disk') ?? config('filesystems.default');
        $disk = Storage::disk($diskName);
        $path = 'health-check/'.now()->format('Y-m-d-His').'.txt';

        try {
            $disk->put($path, 'freedom-minio-ok');
            $this->components->info("Disk [{$diskName}] upload OK: {$path}");
            $this->line('URL: '.$disk->url($path));
            $disk->delete($path);
            $this->components->info('MinIO storage is working.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->components->error('MinIO storage failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
