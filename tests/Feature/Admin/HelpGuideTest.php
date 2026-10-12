<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpGuideTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $this->regularUser = User::factory()->create();
    }

    public function test_admin_can_access_help_guide_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.help'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Help/Index')
            ->has('systemCheck')
        );
    }

    public function test_non_admin_cannot_access_help_guide_page(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.help'));

        $response->assertForbidden();
    }
}
