<?php

namespace Tests\Feature\Agenda;

use App\Models\AgendaItem;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_agenda_item_can_be_created_and_read_back(): void
    {
        $project = Project::factory()->create([
            'name' => 'Lansare anunturi',
            'type' => 'propriu',
        ]);

        $item = AgendaItem::factory()->create([
            'project_id' => $project->id,
            'title' => 'Reinnoieste anunturile',
            'type' => 'task',
            'status' => 'inbox',
            'priority' => 'high',
            'repeat' => '3d',
            'due_at' => now()->addDays(3),
        ]);

        $fresh = AgendaItem::query()->with('project')->findOrFail($item->id);

        $this->assertSame('Reinnoieste anunturile', $fresh->title);
        $this->assertSame('inbox', $fresh->status);
        $this->assertSame('high', $fresh->priority);
        $this->assertSame('3d', $fresh->repeat);
        $this->assertSame('Lansare anunturi', $fresh->project->name);

        $this->assertDatabaseHas('agenda_items', [
            'id' => $item->id,
            'title' => 'Reinnoieste anunturile',
            'status' => 'inbox',
        ]);
    }

    public function test_project_can_be_linked_to_a_client(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->forClient($client)->create(['name' => 'Montaj camera living']);

        $this->assertSame('lucrare', $project->type);
        $this->assertSame($client->id, $project->client_id);
        $this->assertSame($project->id, $client->projects()->first()->id);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'client_id' => $client->id]);
    }

    public function test_project_keeps_open_item_count_through_relation(): void
    {
        $project = Project::factory()->create();
        AgendaItem::factory()->count(2)->create(['project_id' => $project->id, 'status' => 'de_facut']);
        AgendaItem::factory()->create(['project_id' => $project->id, 'status' => 'gata']);

        $this->assertSame(3, $project->agendaItems()->count());
        $this->assertSame(2, $project->agendaItems()->whereNot('status', 'gata')->count());
    }

    public function test_completed_item_status(): void
    {
        $item = AgendaItem::factory()->create(['status' => 'gata', 'completed_at' => now()]);

        $this->assertTrue($item->isCompleted());
        $this->assertNotNull($item->completed_at);
    }
}
