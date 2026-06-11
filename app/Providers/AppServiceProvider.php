<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            return $user?->hasRole('admin') === true ? true : null;
        });

        $this->configureApplicationUrl();

        if (! config('freedom.logo_url')) {
            config(['freedom.logo_url' => self::resolveAssetUrl(config('freedom.logo_path'))]);
        }

        if (! config('freedom.favicon_url')) {
            config(['freedom.favicon_url' => self::resolveAssetUrl(config('freedom.favicon_path')) ?? asset('favicon.svg')]);
        }
    }

    /**
     * Signed Livewire upload URLs must match the browser host (e.g. localhost:8888).
     * A static APP_URL of http://localhost without the port causes upload-file 401 errors.
     */
    private function configureApplicationUrl(): void
    {
        if (app()->runningInConsole()) {
            if ($appUrl = config('app.url')) {
                URL::forceRootUrl($appUrl);

                if ($scheme = parse_url($appUrl, PHP_URL_SCHEME)) {
                    URL::forceScheme($scheme);
                }
            }

            return;
        }

        $scheme = request()->getScheme();

        // In Docker, the app sees SERVER_PORT=80 even though the browser uses :8888.
        // Use APP_URL (which includes the public port) unless the Host header
        // explicitly contains a port.
        $appUrl = config('app.url');
        $host = (string) request()->header('Host', '');

        if ($appUrl) {
            URL::forceRootUrl($appUrl);
            $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: $scheme;
        }

        if ($host !== '' && str_contains($host, ':')) {
            URL::forceRootUrl($scheme.'://'.$host);
        }

        URL::forceScheme($scheme);
    }

    private static function resolveAssetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (config('filesystems.default') === 's3' && config('filesystems.disks.s3.bucket')) {
            $disk = Storage::disk('s3');
            if ($disk->exists($path)) {
                return self::versionedUrl($disk->url($path), $disk->lastModified($path));
            }
        }

        $local = public_path('images/'.$path);

        if (is_file($local)) {
            return self::versionedUrl(asset('images/'.$path), filemtime($local));
        }

        return null;
    }

    private static function versionedUrl(string $url, int $version): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'v='.$version;
    }
}
