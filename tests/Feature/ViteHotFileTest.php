<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ViteHotFileTest extends TestCase
{
    public function test_stale_vite_hot_file_is_removed_on_request(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $hotPath = public_path('hot');
        File::put($hotPath, 'http://127.0.0.1:59999');

        $this->get('/');

        $this->assertFileDoesNotExist($hotPath);
    }

    public function test_legacy_admin_url_redirects_to_configured_path(): void
    {
        $adminPath = config('freedom.admin_path');

        $this->get('/admin/login')
            ->assertRedirect('/'.$adminPath.'/login');
    }
}
