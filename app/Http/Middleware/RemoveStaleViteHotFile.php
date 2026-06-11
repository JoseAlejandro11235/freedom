<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RemoveStaleViteHotFile
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            $this->removeStaleHotFile();
        }

        return $next($request);
    }

    private function removeStaleHotFile(): void
    {
        $hotPath = public_path('hot');

        if (! is_file($hotPath)) {
            return;
        }

        $url = trim((string) file_get_contents($hotPath));

        if ($url === '' || $this->viteIsReachable($url)) {
            return;
        }

        @unlink($hotPath);
    }

    private function viteIsReachable(string $url): bool
    {
        $checkUrl = rtrim($url, '/').'/@vite/client';

        $context = stream_context_create([
            'http' => [
                'timeout' => 1,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($checkUrl, false, $context);

        return $response !== false && $response !== '';
    }
}
