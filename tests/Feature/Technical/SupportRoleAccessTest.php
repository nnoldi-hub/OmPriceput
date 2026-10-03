<?php

namespace Tests\Feature\Technical;

use App\Models\Installation;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $supportUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->supportUser = User::factory()->create();
        $this->supportUser->assignRole('suport');
    }

    public function test_support_user_can_access_technical_dashboard(): void
    {
        $this->actingAs($this->supportUser)
            ->get(route('technical.dashboard'))
            ->assertOk();
    }

    public function test_support_user_can_manage_tickets(): void
    {
        $this->actingAs($this->supportUser)
            ->get(route('technical.tickets.index'))
            ->assertOk();

        $ticket = Ticket::factory()->create();

        $this->actingAs($this->supportUser)
            ->patch(route('technical.tickets.status', $ticket), ['status' => 'resolved'])
            ->assertRedirect();
    }

    public function test_support_user_cannot_manage_equipment(): void
    {
        $this->actingAs($this->supportUser)
            ->get(route('technical.equipment.index'))
            ->assertForbidden();
    }

    public function test_support_user_sees_installations_but_cannot_manage_them(): void
    {
        $this->assertTrue($this->supportUser->hasPermissionTo('installations.view'));
        $this->assertFalse($this->supportUser->hasPermissionTo('installations.manage'));

        $this->actingAs($this->supportUser)
            ->get(route('technical.installations.index'))
            ->assertOk();

        $installation = Installation::factory()->create(['status' => 'scheduled']);

        $this->actingAs($this->supportUser)
            ->get(route('technical.installations.show', $installation))
            ->assertOk();

        $this->actingAs($this->supportUser)
            ->get(route('technical.installations.create'))
            ->assertForbidden();

        $this->actingAs($this->supportUser)
            ->patch(route('technical.installations.status', $installation), ['status' => 'completed'])
            ->assertForbidden();

        $this->assertDatabaseHas('installations', ['id' => $installation->id, 'status' => 'scheduled']);
    }
}
