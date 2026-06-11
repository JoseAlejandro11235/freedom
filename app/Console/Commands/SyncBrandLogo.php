<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncBrandLogo extends Command
{
    protected $signature = 'brand:sync-logo {file? : Path to logo file (default: public/images/{BRAND_LOGO_PATH})}';

    protected $description = 'Upload the brand logo to MinIO / S3';

    public function handle(): int
    {
        $key = config('freedom.logo_path');

        if (! $key) {
            $this->components->error('BRAND_LOGO_PATH is not set.');

            return self::FAILURE;
        }

        $file = $this->argument('file') ?? public_path('images/'.$key);

        if (! is_file($file)) {
            $this->components->error("Logo file not found: {$file}");

            return self::FAILURE;
        }

        $disk = Storage::disk(config('filesystems.default'));

        try {
            $disk->put($key, file_get_contents($file), ['visibility' => 'public']);
        } catch (\Throwable $e) {
            $this->components->error('Upload failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $url = $disk->url($key).'?v='.$disk->lastModified($key);
        $this->components->info("Uploaded [{$key}]");
        $this->line($url);

        return self::SUCCESS;
    }
}
