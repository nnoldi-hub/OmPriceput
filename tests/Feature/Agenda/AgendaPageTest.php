<?php

namespace Tests\Feature\Agenda;

use App\Models\AgendaItem;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_guest_cannot_access_agenda(): void
    {
        $this->get(route('agenda.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_role_cannot_access_agenda(): void
    {
        $sales = User::factory()->create();
        $sales->assignRole('vanzari');

        $this->actingAs($sales)->get(route('agenda.index'))->assertForbidden();
        $this->actingAs($sales)->post(route('agenda.items.store'), ['title' => 'X'])->assertForbidden();
    }

    public function test_admin_sees_inbox_page_with_items(): void
    {
        $item = AgendaItem::factory()->create(['status' => 'inbox', 'title' => 'Idee de adaugat']);

        $this->actingAs($this->admin)
            ->get(route('agenda.inbox'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Agenda/Index')
                ->has('items', 1)
                ->where('items.0.title', 'Idee de adaugat')
                ->where('items.0.status', 'inbox')
                ->has('projects')
            );
    }

    public function test_quick_capture_creates_an_inbox_item(): void
    {
        $this->actingAs($this->admin)
            ->post(route('agenda.items.store'), ['title' => 'Reinnoieste anunturile'])
            ->assertRedirect();

        $this->assertDatabaseHas('agenda_items', [
            'title' => 'Reinnoieste anunturile',
            'status' => 'inbox',
        ]);
    }

    public function test_quick_capture_requires_title(): void
    {
        $this->actingAs($this->admin)
            ->post(route('agenda.items.store'), ['title' => ''])
            ->assertSessionHasErrors('title');
    }

    public function test_quick_capture_ignores_other_fields(): void
    {
        $item = AgendaItem::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('agenda.items.store'), ['title' => 'Numai titlul'])
            ->assertRedirect();

        $this->assertDatabaseHas('agenda_items', ['title' => 'Numai titlul', 'type' => 'task', 'project_id' => null]);
        $this->assertDatabaseMissing('agenda_items', ['id' => $item->id, 'status' => 'arhivat']);
    }

    public function test_inbox_item_can_be_sorted_with_type_project_and_due_date(): void
    {
        $project = Project::factory()->create();
        $item = AgendaItem::factory()->create(['status' => 'inbox']);

        $this->actingAs($this->admin)->patch(route('agenda.items.update', $item), [
            'title' => $item->title,
            'text' => $item->text,
            'type' => 'idee',
            'status' => 'inbox',
            'priority' => 'high',
            'project_id' => $project->id,
            'due_at' => '2026-10-20',
        ])->assertRedirect();

        $this->assertDatabaseHas('agenda_items', [
            'id' => $item->id,
            'type' => 'idee',
            'priority' => 'high',
            'project_id' => $project->id,
            'due_at' => '2026-10-20 00:00:00',
        ]);
    }

    public function test_completing_an_item_sets_completed_at(): void
    {
        $item = AgendaItem::factory()->create(['status' => 'de_facut']);

        $this->actingAs($this->admin)->patch(route('agenda.items.update', $item), [
            'title' => $item->title,
            'type' => 'task',
            'status' => 'gata',
            'priority' => 'normal',
            'project_id' => null,
            'due_at' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('agenda_items', ['id' => $item->id, 'status' => 'gata']);
        $this->assertNotNull($item->fresh()->completed_at);
    }

    public function test_moving_item_away_from_gata_clears_completed_at(): void
    {
        $item = AgendaItem::factory()->create(['status' => 'gata', 'completed_at' => now()]);

        $this->actingAs($this->admin)->patch(route('agenda.items.update', $item), [
            'title' => $item->title,
            'type' => 'task',
            'status' => 'de_facut',
            'priority' => 'normal',
            'project_id' => null,
            'due_at' => null,
        ])->assertRedirect();

        $this->assertNull($item->fresh()->completed_at);
    }

    public function test_archived_items_do_not_appear_in_the_inbox(): void
    {
        AgendaItem::factory()->create(['status' => 'arhivat', 'title' => 'Ascuns']);

        $this->actingAs($this->admin)
            ->get(route('agenda.inbox'))
            ->assertInertia(fn ($page) => $page->has('items', 0));
    }

    public function test_item_can_be_deleted(): void
    {
        $item = AgendaItem::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('agenda.items.destroy', $item))
            ->assertRedirect();

        $this->assertDatabaseMissing('agenda_items', ['id' => $item->id]);
    }
}
