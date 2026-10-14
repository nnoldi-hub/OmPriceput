<?php

namespace Tests\Feature\Agenda;

use App\Models\AgendaItem;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\ProjectSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPageTest extends TestCase
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

    public function test_non_admin_cannot_access_projects(): void
    {
        $sales = User::factory()->create();
        $sales->assignRole('vanzari');

        $this->actingAs($sales)->get(route('agenda.projects.index'))->assertForbidden();
    }

    public function test_projects_index_lists_projects_with_open_counts(): void
    {
        $project = Project::factory()->create(['name' => 'Lansare anunturi', 'status' => 'activ']);
        AgendaItem::factory()->create(['project_id' => $project->id, 'status' => 'de_facut']);
        AgendaItem::factory()->create(['project_id' => $project->id, 'status' => 'gata']);
        AgendaItem::factory()->create(['project_id' => $project->id, 'status' => 'arhivat']);
        Project::factory()->create(['name' => 'Fara taskuri', 'status' => 'activ']);

        $this->actingAs($this->admin)
            ->get(route('agenda.projects.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Agenda/Projects/Index')
                ->has('projects', 2)
                ->where('projects.0.open_items_count', 0)
                ->where('projects.1.name', 'Lansare anunturi')
                ->where('projects.1.open_items_count', 1)
                ->where('projects.1.done_items_count', 1)
                ->where('projects.1.total_items_count', 2)
            );
    }

    public function test_project_can_be_created_with_optional_client(): void
    {
        $client = Client::factory()->create(['name' => 'Maria Ionescu']);

        $this->actingAs($this->admin)->post(route('agenda.projects.store'), [
            'name' => 'Montaj camera living',
            'type' => 'lucrare',
            'status' => 'activ',
            'client_id' => $client->id,
            'description' => 'Living principal',
        ])->assertRedirect(route('agenda.projects.show', Project::first()));

        $this->assertDatabaseHas('projects', [
            'name' => 'Montaj camera living',
            'type' => 'lucrare',
            'client_id' => $client->id,
        ]);
    }

    public function test_project_creation_requires_name(): void
    {
        $this->actingAs($this->admin)
            ->post(route('agenda.projects.store'), ['type' => 'propriu', 'status' => 'activ'])
            ->assertSessionHasErrors('name');
    }

    public function test_project_page_shows_items_and_progress(): void
    {
        $project = Project::factory()->create();
        AgendaItem::factory()->create(['project_id' => $project->id, 'status' => 'de_facut', 'title' => 'Task deschis']);
        AgendaItem::factory()->create(['project_id' => $project->id, 'status' => 'gata', 'title' => 'Task gata']);
        AgendaItem::factory()->create(['project_id' => $project->id, 'status' => 'inbox', 'type' => 'idee', 'title' => 'Idee noua']);
        AgendaItem::factory()->create(['project_id' => $project->id, 'status' => 'arhivat', 'title' => 'Ascuns']);

        $this->actingAs($this->admin)
            ->get(route('agenda.projects.show', $project))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Agenda/Projects/Show')
                ->where('project.id', $project->id)
                ->where('progress', 33)
                ->has('items', 3)
                ->where('items.2.status', 'gata')
            );
    }

    public function test_project_can_be_updated(): void
    {
        $project = Project::factory()->create(['status' => 'activ', 'name' => 'Nume vechi']);

        $this->actingAs($this->admin)->patch(route('agenda.projects.update', $project), [
            'name' => 'Nume nou',
            'type' => 'propriu',
            'status' => 'in_pauza',
            'client_id' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Nume nou', 'status' => 'in_pauza']);
    }

    public function test_project_can_be_deleted(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('agenda.projects.destroy', $project))
            ->assertRedirect(route('agenda.projects.index'));

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_item_can_be_added_directly_to_a_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->admin)->post(route('agenda.items.store'), [
            'title' => 'Task din proiect',
            'type' => 'idee',
            'status' => 'de_facut',
            'project_id' => $project->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('agenda_items', [
            'title' => 'Task din proiect',
            'type' => 'idee',
            'status' => 'de_facut',
            'project_id' => $project->id,
        ]);
    }

    public function test_starter_projects_are_seeded_once(): void
    {
        $this->seed(ProjectSeeder::class);
        $this->seed(ProjectSeeder::class);

        $this->assertDatabaseCount('projects', 4);
        $this->assertDatabaseHas('projects', ['name' => 'Lansare anunturi', 'type' => 'propriu']);
        $this->assertDatabaseHas('projects', ['name' => 'Catalog materiale']);
        $this->assertDatabaseHas('projects', ['name' => 'Blog si SEO']);
        $this->assertDatabaseHas('projects', ['name' => 'Cutii de scule']);
    }
}
