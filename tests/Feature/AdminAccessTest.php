<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_customers_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $this->actingAs($user)
            ->get('/'.config('freedom.admin_path'))
            ->assertForbidden();
    }

    public function test_admins_can_access_admin_panel(): void
    {
        Role::findOrCreate('admin', 'web');

        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->givePermissionTo('access-admin-panel');

        $this->actingAs($user)
            ->get('/'.config('freedom.admin_path'))
            ->assertOk()
            ->assertSee('Products');
    }
}
