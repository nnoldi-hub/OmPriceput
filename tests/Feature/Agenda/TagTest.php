<?php

namespace Tests\Feature\Agenda;

use App\Models\AgendaItem;
use App\Models\AgendaTag;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
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

    public function test_store_creates_and_attaches_tags(): void
    {
        $this->actingAs($this->admin)->post(route('agenda.items.store'), [
            'title' => 'Anunt nou',
            'tags' => ['Utilaj', 'iarna'],
        ])->assertRedirect();

        $item = AgendaItem::where('title', 'Anunt nou')->first();
        $this->assertSame(['Utilaj', 'iarna'], $item->tags->pluck('name')->all());
        $this->assertDatabaseHas('agenda_tags', ['name' => 'Utilaj']);
    }

    public function test_update_resyncs_tags(): void
    {
        $item = AgendaItem::factory()->create();
        $item->tags()->attach(AgendaTag::create(['name' => 'Vechi']));

        $this->actingAs($this->admin)->patch(route('agenda.items.update', $item), [
            'tags' => ['Nou'],
        ])->assertRedirect();

        $this->assertSame(['Nou'], $item->fresh()->tags->pluck('name')->all());
    }

    public function test_inbox_filters_by_tag(): void
    {
        $matching = AgendaItem::factory()->create(['title' => 'Cu eticheta']);
        $matching->tags()->attach(AgendaTag::create(['name' => 'Utilaj']));
        AgendaItem::factory()->create(['title' => 'Fara eticheta']);

        $this->actingAs($this->admin)
            ->get(route('agenda.inbox', ['tag' => 'Utilaj']))
            ->assertInertia(fn ($page) => $page
                ->where('tag', 'Utilaj')
                ->has('items', 1)
                ->where('items.0.title', 'Cu eticheta')
                ->where('tags.0.name', 'Utilaj')
            );
    }

    public function test_empty_tag_filter_returns_everything(): void
    {
        AgendaItem::factory()->count(2)->create();

        $this->actingAs($this->admin)
            ->get(route('agenda.inbox'))
            ->assertInertia(fn ($page) => $page->where('tag', '')->has('items', 2));
    }

    public function test_search_returns_agenda_results_for_admin(): void
    {
        $item = AgendaItem::factory()->create(['title' => 'Reinnoieste anunturile OLX']);
        $project = Project::factory()->create(['name' => 'Lansare anunturi']);
        $client = Client::factory()->create(['name' => 'Anunturi Bine SRL']);

        $this->actingAs($this->admin)
            ->getJson(route('search', ['q' => 'anunturi']))
            ->assertOk()
            ->assertJsonPath('agenda_items.0.id', $item->id)
            ->assertJsonPath('projects.0.id', $project->id)
            ->assertJsonPath('clients.0.id', $client->id);
    }

    public function test_search_hides_agenda_results_from_non_admin(): void
    {
        $sales = User::factory()->create();
        $sales->assignRole('vanzari');

        AgendaItem::factory()->create(['title' => 'Reinnoieste anunturile OLX']);

        $this->actingAs($sales)
            ->getJson(route('search', ['q' => 'anunturile']))
            ->assertOk()
            ->assertJsonPath('agenda_items', [])
            ->assertJsonPath('projects', []);
    }

    public function test_search_requires_minimum_two_characters(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('search', ['q' => 'a']))
            ->assertOk()
            ->assertJsonPath('agenda_items', []);
    }
}
