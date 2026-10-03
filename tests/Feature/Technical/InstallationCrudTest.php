<?php

namespace Tests\Feature\Technical;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\Installation;
use App\Models\Invoice;
use App\Models\Offer;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallationCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $techUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->techUser = User::factory()->create();
        $this->techUser->assignRole('tehnic');
    }

    public function test_technician_can_create_installation_with_default_checklist(): void
    {
        $client = Client::factory()->create();

        $response = $this->actingAs($this->techUser)->post(route('technical.installations.store'), [
            'client_id' => $client->id,
            'type' => 'instalare',
            'address' => 'Str. Exemplu nr. 1',
            'status' => 'scheduled',
        ]);

        $installation = Installation::firstWhere('client_id', $client->id);
        $response->assertRedirect(route('technical.installations.show', $installation));

        $this->assertNotEmpty($installation->checklist);
        $this->assertFalse($installation->checklist[0]['done']);
    }

    public function test_technician_cannot_be_scheduled_twice_at_the_same_time(): void
    {
        $client = Client::factory()->create();
        $technician = User::factory()->create();
        $technician->assignRole('tehnic');

        Installation::factory()->create([
            'client_id' => $client->id,
            'technician_id' => $technician->id,
            'scheduled_at' => '2026-09-15 10:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->techUser)->post(route('technical.installations.store'), [
            'client_id' => $client->id,
            'technician_id' => $technician->id,
            'type' => 'reparatie',
            'scheduled_at' => '2026-09-15 10:00:00',
            'status' => 'scheduled',
            'material_items' => [],
            'service_items' => [],
        ]);

        $response->assertSessionHasErrors('scheduled_at');
        $this->assertDatabaseCount('installations', 1);
    }

    public function test_technician_can_toggle_checklist_item(): void
    {
        $installation = Installation::factory()->create();

        $this->actingAs($this->techUser)
            ->patch(route('technical.installations.checklist', $installation), ['index' => 0, 'done' => true])
            ->assertRedirect();

        $installation->refresh();
        $this->assertTrue($installation->checklist[0]['done']);
    }

    public function test_technician_can_view_daily_installation_calendar(): void
    {
        $installation = Installation::factory()->create([
            'scheduled_at' => '2026-09-11 09:30:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->techUser)
            ->get(route('technical.installations.calendar', ['date' => '2026-09-11']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Technical/Installations/Calendar')
                ->where('view', 'day')
                ->where('date', '2026-09-11')
                ->has('installations', 1)
                ->where('installations.0.id', $installation->id)
            );
    }

    public function test_technician_can_view_weekly_installation_calendar(): void
    {
        $inWeek = Installation::factory()->create([
            'scheduled_at' => '2026-09-10 09:30:00',
            'status' => 'scheduled',
        ]);
        Installation::factory()->create([
            'scheduled_at' => '2026-09-20 09:30:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->techUser)
            ->get(route('technical.installations.calendar', ['date' => '2026-09-11', 'view' => 'week']));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Technical/Installations/Calendar')
                ->where('view', 'week')
                ->has('days', 7)
                ->where('installations.0.id', $inWeek->id)
            );
    }

    public function test_technician_can_view_monthly_installation_calendar(): void
    {
        Installation::factory()->create([
            'scheduled_at' => '2026-09-15 09:30:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->techUser)
            ->get(route('technical.installations.calendar', ['date' => '2026-09-01', 'view' => 'month']));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Technical/Installations/Calendar')
                ->where('view', 'month')
                ->has('installations', 1)
            );
    }

    public function test_toggling_one_checklist_item_does_not_affect_others(): void
    {
        $installation = Installation::factory()->create();

        $this->actingAs($this->techUser)
            ->patch(route('technical.installations.checklist', $installation), ['index' => 0, 'done' => true]);
        $this->actingAs($this->techUser)
            ->patch(route('technical.installations.checklist', $installation), ['index' => 2, 'done' => true]);

        $installation->refresh();
        $this->assertTrue($installation->checklist[0]['done']);
        $this->assertFalse($installation->checklist[1]['done']);
        $this->assertTrue($installation->checklist[2]['done']);
    }

    public function test_technician_can_update_installation_status(): void
    {
        $installation = Installation::factory()->create(['status' => 'scheduled']);

        $this->actingAs($this->techUser)
            ->patch(route('technical.installations.status', $installation), ['status' => 'completed'])
            ->assertRedirect();

        $this->assertDatabaseHas('installations', ['id' => $installation->id, 'status' => 'completed']);
        $installation->refresh();
        $this->assertSame('PV-'.now()->format('Y').'-'.str_pad((string) $installation->id, 5, '0', STR_PAD_LEFT), $installation->report_number);
        $this->assertNotNull($installation->completed_at);
        $this->assertNotNull($installation->handover_at);
    }

    public function test_completing_an_installation_linked_to_an_offer_creates_an_invoice(): void
    {
        $client = Client::factory()->create();
        $offer = Offer::factory()->create(['client_id' => $client->id, 'total_amount' => 2500]);
        $installation = Installation::factory()->create(['client_id' => $client->id, 'offer_id' => $offer->id, 'status' => 'in_progress']);

        $this->actingAs($this->techUser)
            ->patch(route('technical.installations.status', $installation), ['status' => 'completed']);

        $invoice = Invoice::firstWhere('offer_id', $offer->id);
        $this->assertNotNull($invoice);
        $this->assertEquals(2500, $invoice->amount);
        $this->assertEquals('unpaid', $invoice->status);
    }

    public function test_completing_an_installation_without_an_offer_does_not_create_an_invoice(): void
    {
        $installation = Installation::factory()->create(['offer_id' => null, 'status' => 'in_progress']);

        $this->actingAs($this->techUser)
            ->patch(route('technical.installations.status', $installation), ['status' => 'completed']);

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_completing_an_installation_consumes_selected_stock_once(): void
    {
        $equipment = Equipment::factory()->create(['stock_quantity' => 10, 'name' => 'Camera IP']);
        $installation = Installation::factory()->create([
            'status' => 'in_progress',
            'material_items' => [['equipment_id' => $equipment->id, 'quantity' => 3]],
        ]);

        $this->actingAs($this->techUser)
            ->patch(route('technical.installations.status', $installation), ['status' => 'completed'])
            ->assertRedirect();

        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'stock_quantity' => 7]);
        $installation->refresh();
        $this->assertNotNull($installation->stock_consumed_at);

        $this->actingAs($this->techUser)
            ->patch(route('technical.installations.status', $installation), ['status' => 'in_progress']);
        $this->actingAs($this->techUser)
            ->patch(route('technical.installations.status', $installation), ['status' => 'completed']);

        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'stock_quantity' => 7]);
    }

    public function test_installation_completion_fails_without_changing_stock_when_stock_is_insufficient(): void
    {
        $equipment = Equipment::factory()->create(['stock_quantity' => 1, 'name' => 'NVR']);
        $installation = Installation::factory()->create([
            'status' => 'in_progress',
            'material_items' => [['equipment_id' => $equipment->id, 'quantity' => 2]],
        ]);

        $this->actingAs($this->techUser)
            ->patch(route('technical.installations.status', $installation), ['status' => 'completed'])
            ->assertSessionHasErrors('material_items');

        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'stock_quantity' => 1]);
        $this->assertDatabaseHas('installations', ['id' => $installation->id, 'status' => 'in_progress']);
    }

    public function test_completing_an_installation_twice_does_not_duplicate_the_invoice(): void
    {
        $offer = Offer::factory()->create(['total_amount' => 1000]);
        $installation = Installation::factory()->create(['offer_id' => $offer->id, 'status' => 'in_progress']);

        $this->actingAs($this->techUser)->patch(route('technical.installations.status', $installation), ['status' => 'completed']);
        $this->actingAs($this->techUser)->patch(route('technical.installations.status', $installation), ['status' => 'completed']);

        $this->assertEquals(1, Invoice::where('offer_id', $offer->id)->count());
    }

    public function test_finalized_installation_cannot_be_reopened_or_edited(): void
    {
        $installation = Installation::factory()->create([
            'status' => 'completed',
            'completed_at' => now(),
            'handover_at' => now(),
            'report_number' => 'PV-2026-00001',
        ]);

        $this->actingAs($this->techUser)
            ->patch(route('technical.installations.status', $installation), ['status' => 'in_progress'])
            ->assertSessionHasErrors('status');

        $this->actingAs($this->techUser)
            ->put(route('technical.installations.update', $installation), [
                'client_id' => $installation->client_id,
                'type' => $installation->type,
                'status' => 'completed',
            ])
            ->assertSessionHasErrors('installation');
    }

    public function test_installation_report_pdf_can_be_downloaded(): void
    {
        $installation = Installation::factory()->create();

        $this->actingAs($this->techUser)
            ->get(route('technical.installations.pdf', $installation))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_installation_cost_report_calculates_materials_services_and_profit(): void
    {
        $equipment = Equipment::factory()->create(['cost_price' => 150]);
        $service = Service::create([
            'name' => 'Montaj prize',
            'category' => 'electrice',
            'unit' => 'ora',
            'cost_price' => 200,
            'sale_price' => 500,
            'is_active' => true,
        ]);
        $offer = Offer::factory()->create(['total_amount' => 1500]);
        $installation = Installation::factory()->create([
            'offer_id' => $offer->id,
            'material_items' => [['equipment_id' => $equipment->id, 'quantity' => 2]],
            'service_items' => [['service_id' => $service->id, 'quantity' => 1]],
        ]);

        $this->assertSame(300.0, $installation->cost_report['material_cost']);
        $this->assertSame(200.0, $installation->cost_report['labor_cost']);
        $this->assertSame(500.0, $installation->cost_report['total_cost']);
        $this->assertSame(1000.0, $installation->cost_report['estimated_profit']);
    }

    public function test_installations_can_be_filtered_by_type(): void
    {
        Installation::factory()->create(['type' => 'instalare']);
        Installation::factory()->create(['type' => 'reparatie']);

        $response = $this->actingAs($this->techUser)
            ->get(route('technical.installations.index', ['type' => 'reparatie']));

        $response->assertInertia(fn ($page) => $page
            ->component('Technical/Installations/Index')
            ->has('installations.data', 1)
        );
    }
}
