<?php

namespace Tests\Feature\Measurement;

use App\Models\Client;
use App\Models\Measurement;
use App\Models\MeasurementObject;
use App\Models\Offer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementOfferTest extends TestCase
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

    public function test_conversion_requires_a_client(): void
    {
        $measurement = Measurement::factory()->create(['client_id' => null]);
        MeasurementObject::factory()->create(['measurement_id' => $measurement->id, 'length' => 4, 'width' => 3]);

        $this->actingAs($this->admin)
            ->post(route('measurements.offer', $measurement))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('offers', 0);
    }

    public function test_conversion_creates_offer_with_labor_items(): void
    {
        $client = Client::factory()->create(['name' => 'Maria Ionescu']);
        $measurement = Measurement::factory()->forClient($client)->create(['name' => 'Casa Unirii']);
        MeasurementObject::factory()->create([
            'measurement_id' => $measurement->id,
            'name' => 'Living',
            'length' => 4.5,
            'width' => 3.2,
            'quantity' => 1,
        ]);

        $this->actingAs($this->admin)
            ->post(route('measurements.offer', $measurement))
            ->assertRedirect(route('sales.offers.show', Offer::first()));

        $offer = Offer::first();
        $this->assertSame('Oferta - Casa Unirii', $offer->title);
        $this->assertSame($client->id, $offer->client_id);
        $this->assertSame($measurement->id, $offer->measurement_id);
        $this->assertSame('draft', $offer->status);
        $this->assertSame('instalare', $offer->job_type);

        $area = $offer->items->first(fn ($item) => $item->unit === 'mp');
        $this->assertNotNull($area);
        $this->assertStringContainsString('Living', $area->description);
        $this->assertSame(14, (int) $area->quantity);
        $this->assertSame(25.0, (float) $area->unit_price);

        $cable = $offer->items->first(fn ($item) => $item->unit === 'ml');
        $this->assertNotNull($cable);
        $this->assertSame(15, (int) $cable->quantity);
        $this->assertSame(10.0, (float) $cable->unit_price);

        $this->assertSame(500.0, (float) $offer->total_amount);
    }

    public function test_conversion_keeps_objects_without_dimensions_as_unit_lines(): void
    {
        $client = Client::factory()->create();
        $measurement = Measurement::factory()->forClient($client)->create();
        MeasurementObject::factory()->create([
            'measurement_id' => $measurement->id,
            'name' => 'Necompletat',
            'length' => null,
            'width' => null,
            'quantity' => 2,
        ]);

        $this->actingAs($this->admin)
            ->post(route('measurements.offer', $measurement))
            ->assertRedirect(route('sales.offers.show', Offer::first()));

        $offer = Offer::first();
        $this->assertSame(1, $offer->items->count());

        $line = $offer->items->first();
        $this->assertSame('Necompletat', $line->description);
        $this->assertSame('labor', $line->section);
        $this->assertSame(2, (int) $line->quantity);
        $this->assertSame('buc', $line->unit);
        $this->assertSame(0.0, (float) $line->unit_price);
        $this->assertSame(0.0, (float) $offer->total_amount);
    }

    public function test_conversion_reuses_existing_offer_for_measurement(): void
    {
        $client = Client::factory()->create();
        $measurement = Measurement::factory()->forClient($client)->create();
        MeasurementObject::factory()->create(['measurement_id' => $measurement->id, 'length' => 3, 'width' => 2]);
        $existing = Offer::factory()->create(['client_id' => $client->id, 'measurement_id' => $measurement->id]);

        $this->actingAs($this->admin)
            ->post(route('measurements.offer', $measurement))
            ->assertRedirect(route('sales.offers.show', $existing));

        $this->assertSame(1, Offer::where('measurement_id', $measurement->id)->count());
    }

    public function test_show_renderer_exposes_linked_offer(): void
    {
        $client = Client::factory()->create();
        $measurement = Measurement::factory()->forClient($client)->create();
        $offer = Offer::factory()->create([
            'client_id' => $client->id,
            'measurement_id' => $measurement->id,
            'title' => 'Oferta - Casa',
            'total_amount' => 500,
        ]);

        $this->actingAs($this->admin)
            ->get(route('measurements.show', $measurement))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Measurements/Show')
                ->where('measurement.offer.id', $offer->id)
                ->where('measurement.offer.title', 'Oferta - Casa')
            );
    }
}
