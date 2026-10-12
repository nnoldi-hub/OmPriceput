<?php

namespace Tests\Feature\Measurement;

use App\Models\Client;
use App\Models\Measurement;
use App\Models\MeasurementObject;
use App\Models\Offer;
use App\Models\PriceItem;
use App\Models\User;
use Database\Seeders\PriceItemSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementTypedObjectsTest extends TestCase
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

    public function test_object_stores_type_unit_and_attributes(): void
    {
        $measurement = Measurement::factory()->create();

        $this->actingAs($this->admin)->post(route('measurements.objects.store', $measurement), [
            'name' => 'Tablou casa',
            'object_type' => 'tablou',
            'quantity' => 1,
            'specs' => ['sigurante' => 12, 'faze' => 'Trifazat'],
        ])->assertRedirect();

        $object = MeasurementObject::first();
        $this->assertSame('tablou', $object->object_type);
        $this->assertSame('buc', $object->unit);
        $this->assertSame(12, $object->specs['sigurante']);
        $this->assertSame('Nr. sigurante: 12, Faze: Trifazat', $object->attributesSummary());
    }

    public function test_socket_and_route_units_are_computed_from_type(): void
    {
        $measurement = Measurement::factory()->create();
        $priza = MeasurementObject::factory()->create([
            'measurement_id' => $measurement->id,
            'object_type' => 'priza',
            'unit' => 'buc',
            'length' => null,
            'width' => null,
            'quantity' => 12,
        ]);
        $traseu = MeasurementObject::factory()->create([
            'measurement_id' => $measurement->id,
            'object_type' => 'traseu',
            'unit' => 'ml',
            'length' => 20,
            'width' => null,
            'quantity' => 2,
        ]);

        $this->assertSame(12.0, $priza->unitQuantity());
        $this->assertSame(40.0, $traseu->unitQuantity());
        $this->assertSame(0.0, $traseu->area());
    }

    public function test_conversion_uses_priced_types_from_nomenclator(): void
    {
        $this->seed(PriceItemSeeder::class);

        $client = Client::factory()->create();
        $measurement = Measurement::factory()->forClient($client)->create();

        MeasurementObject::factory()->create([
            'measurement_id' => $measurement->id,
            'object_type' => 'priza',
            'unit' => 'buc',
            'length' => null,
            'width' => null,
            'quantity' => 12,
        ]);
        MeasurementObject::factory()->create([
            'measurement_id' => $measurement->id,
            'object_type' => 'traseu',
            'unit' => 'ml',
            'length' => 20,
            'width' => null,
            'quantity' => 1,
        ]);
        MeasurementObject::factory()->create([
            'measurement_id' => $measurement->id,
            'object_type' => 'tablou',
            'unit' => 'buc',
            'length' => null,
            'width' => null,
            'quantity' => 1,
            'specs' => ['sigurante' => 8, 'faze' => 'Trifazat'],
        ]);

        $this->actingAs($this->admin)
            ->post(route('measurements.offer', $measurement))
            ->assertRedirect(route('sales.offers.show', Offer::first()));

        $offer = Offer::first();
        $this->assertSame(3, $offer->items->count());
        $this->assertNull($offer->items->first(fn ($item) => str_contains($item->description, 'Cablaj retea')));

        $priza = $offer->items->first(fn ($item) => str_contains($item->description, 'Priza'));
        $this->assertSame('buc', $priza->unit);
        $this->assertSame(12, (int) $priza->quantity);
        $this->assertSame(25.0, (float) $priza->unit_price);

        $traseu = $offer->items->first(fn ($item) => str_contains($item->description, 'Traseu'));
        $this->assertSame('ml', $traseu->unit);
        $this->assertSame(20, (int) $traseu->quantity);
        $this->assertSame(10.0, (float) $traseu->unit_price);

        $tablou = $offer->items->first(fn ($item) => str_contains($item->description, 'Tablou'));
        $this->assertSame(1, (int) $tablou->quantity);
        $this->assertSame(250.0, (float) $tablou->unit_price);
        $this->assertStringContainsString('Nr. sigurante: 8', $tablou->description);

        $this->assertSame(750.0, (float) $offer->total_amount);
    }

    public function test_show_estimate_includes_typed_objects(): void
    {
        $this->seed(PriceItemSeeder::class);

        $measurement = Measurement::factory()->create();
        MeasurementObject::factory()->create([
            'measurement_id' => $measurement->id,
            'object_type' => 'priza',
            'unit' => 'buc',
            'length' => null,
            'width' => null,
            'quantity' => 12,
        ]);

        $this->actingAs($this->admin)
            ->get(route('measurements.show', $measurement))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('pricing.estimate', 300)
                ->where('pricing.type_prices.priza', 25)
                ->where('measurement.objects.0.unit', 'buc')
                ->where('measurement.objects.0.unit_quantity', 12)
            );
    }

    public function test_price_for_returns_null_when_type_is_missing(): void
    {
        $this->assertNull(PriceItem::priceFor('priza'));
    }
}
