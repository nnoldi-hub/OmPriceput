<?php

namespace Tests\Feature\Technical;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\Installation;
use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\MaterialRequirementService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialRequirementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function technician(): User
    {
        $user = User::factory()->create();
        $user->assignRole('tehnic');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function build(array $filters = []): array
    {
        return app(MaterialRequirementService::class)->build($filters);
    }

    public function test_requirement_compares_needed_quantity_with_stock(): void
    {
        $equipment = Equipment::factory()->create(['name' => 'Conector Wago', 'stock_quantity' => 4, 'cost_price' => 10]);
        Installation::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'material_items' => [['equipment_id' => $equipment->id, 'name' => 'Conector Wago', 'unit' => 'buc', 'quantity' => 12]],
        ]);

        $result = $this->build();

        $this->assertCount(1, $result['rows']);
        $row = $result['rows'][0];
        $this->assertSame(12.0, $row['needed']);
        $this->assertSame(4.0, $row['in_stock']);
        $this->assertSame(8.0, $row['to_buy']);
        $this->assertSame(80.0, $row['estimated_cost']);
        $this->assertSame(80.0, $result['totals']['estimated_cost']);
        $this->assertSame(1, $result['totals']['to_buy_lines']);
    }

    public function test_free_text_materials_are_always_to_buy(): void
    {
        Installation::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'material_items' => [['equipment_id' => null, 'name' => 'Șurub special', 'unit' => 'buc', 'quantity' => 5]],
        ]);

        $result = $this->build();

        $this->assertCount(1, $result['rows']);
        $this->assertFalse($result['rows'][0]['in_catalog']);
        $this->assertSame(5.0, $result['rows'][0]['to_buy']);
    }

    public function test_accepted_offer_without_installation_contributes_materials(): void
    {
        $client = Client::factory()->create();
        $offer = Offer::factory()->create(['client_id' => $client->id, 'status' => 'accepted']);
        OfferItem::create([
            'offer_id' => $offer->id,
            'section' => OfferItem::SECTION_MATERIALS,
            'description' => 'Bandă LED',
            'quantity' => 3,
            'unit' => 'buc',
            'unit_price' => 20,
        ]);

        $result = $this->build();

        $this->assertCount(1, $result['rows']);
        $this->assertSame('Bandă LED', $result['rows'][0]['name']);
        $this->assertSame(3.0, $result['rows'][0]['needed']);
    }

    public function test_accepted_offer_with_installation_is_not_double_counted(): void
    {
        $equipment = Equipment::factory()->create(['stock_quantity' => 0]);
        $offer = Offer::factory()->create(['status' => 'accepted']);
        OfferItem::create([
            'offer_id' => $offer->id,
            'equipment_id' => $equipment->id,
            'section' => OfferItem::SECTION_MATERIALS,
            'description' => 'Material',
            'quantity' => 3,
            'unit' => 'buc',
            'unit_price' => 20,
        ]);
        Installation::factory()->create([
            'offer_id' => $offer->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'material_items' => [['equipment_id' => $equipment->id, 'name' => $equipment->name, 'unit' => 'buc', 'quantity' => 3]],
        ]);

        $result = $this->build();

        $this->assertCount(1, $result['rows']);
        $this->assertSame(3.0, $result['rows'][0]['needed']);
    }

    public function test_finalized_and_cancelled_installations_are_ignored(): void
    {
        $equipment = Equipment::factory()->create(['stock_quantity' => 0]);
        Installation::factory()->create([
            'status' => 'completed',
            'material_items' => [['equipment_id' => $equipment->id, 'name' => 'X', 'unit' => 'buc', 'quantity' => 5]],
        ]);
        Installation::factory()->create([
            'status' => 'cancelled',
            'material_items' => [['equipment_id' => $equipment->id, 'name' => 'X', 'unit' => 'buc', 'quantity' => 5]],
        ]);

        $result = $this->build();

        $this->assertCount(0, $result['rows']);
    }

    public function test_installation_filter_limits_the_requirement_to_one_job(): void
    {
        $equipment = Equipment::factory()->create(['stock_quantity' => 0]);
        $installation = Installation::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'material_items' => [['equipment_id' => $equipment->id, 'name' => 'A', 'unit' => 'buc', 'quantity' => 2]],
        ]);
        Installation::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'material_items' => [['equipment_id' => $equipment->id, 'name' => 'A', 'unit' => 'buc', 'quantity' => 9]],
        ]);

        $result = $this->build(['installation_id' => $installation->id]);

        $this->assertCount(1, $result['rows']);
        $this->assertSame(2.0, $result['rows'][0]['needed']);
    }

    public function test_period_filter_excludes_jobs_outside_the_range(): void
    {
        $equipment = Equipment::factory()->create(['stock_quantity' => 0]);
        Installation::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'material_items' => [['equipment_id' => $equipment->id, 'name' => 'In period', 'unit' => 'buc', 'quantity' => 2]],
        ]);
        Installation::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(30),
            'material_items' => [['equipment_id' => $equipment->id, 'name' => 'Out of period', 'unit' => 'buc', 'quantity' => 50]],
        ]);

        $result = $this->build([
            'from' => now()->startOfDay(),
            'to' => now()->addDays(7)->endOfDay(),
            'include_unscheduled' => false,
        ]);

        $this->assertSame(2.0, $result['rows'][0]['needed']);
    }

    public function test_only_missing_filter_hides_covered_items(): void
    {
        $covered = Equipment::factory()->create(['stock_quantity' => 10, 'name' => 'Covered']);
        $missing = Equipment::factory()->create(['stock_quantity' => 0, 'name' => 'Missing']);
        Installation::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'material_items' => [
                ['equipment_id' => $covered->id, 'name' => 'Covered', 'unit' => 'buc', 'quantity' => 5],
                ['equipment_id' => $missing->id, 'name' => 'Missing', 'unit' => 'buc', 'quantity' => 5],
            ],
        ]);

        $result = $this->build(['only_missing' => true]);

        $this->assertCount(1, $result['rows']);
        $this->assertSame('Missing', $result['rows'][0]['name']);
    }

    public function test_shopping_page_groups_by_supplier_and_excludes_covered_items(): void
    {
        $supplier = Supplier::create(['name' => 'Depozit Central']);
        $equipment = Equipment::factory()->create(['stock_quantity' => 0, 'cost_price' => 5, 'supplier_id' => $supplier->id]);
        Installation::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'material_items' => [['equipment_id' => $equipment->id, 'name' => $equipment->name, 'unit' => 'buc', 'quantity' => 4]],
        ]);
        $covered = Equipment::factory()->create(['stock_quantity' => 100]);
        Installation::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'material_items' => [['equipment_id' => $covered->id, 'name' => $covered->name, 'unit' => 'buc', 'quantity' => 3]],
        ]);

        $this->actingAs($this->technician())
            ->get(route('technical.requirements.shopping'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Technical/Requirements/Shopping')
                ->has('groups', 1)
                ->where('groups.0.supplier_name', 'Depozit Central')
                ->has('groups.0.items', 1)
            );
    }

    public function test_necesar_page_is_available_to_technical_role(): void
    {
        $this->actingAs($this->technician())
            ->get(route('technical.requirements.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Technical/Requirements/Index'));
    }

    public function test_requirement_pages_require_equipment_permission(): void
    {
        $support = User::factory()->create();
        $support->assignRole('suport');

        $this->actingAs($support)->get(route('technical.requirements.index'))->assertForbidden();
        $this->actingAs($support)->get(route('technical.requirements.shopping'))->assertForbidden();
    }

    public function test_shopping_list_exports_pdf_and_excel(): void
    {
        $equipment = Equipment::factory()->create(['stock_quantity' => 0]);
        Installation::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'material_items' => [['equipment_id' => $equipment->id, 'name' => $equipment->name, 'unit' => 'buc', 'quantity' => 4]],
        ]);

        $this->actingAs($this->technician())
            ->get(route('technical.requirements.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->technician())
            ->get(route('technical.requirements.excel'))
            ->assertOk();
    }

    public function test_generating_purchase_orders_groups_by_supplier_and_skips_unlinked_items(): void
    {
        $supplierA = Supplier::create(['name' => 'Depozit A']);
        $supplierB = Supplier::create(['name' => 'Depozit B']);
        $withSupplierA = Equipment::factory()->create(['stock_quantity' => 0, 'cost_price' => 10, 'supplier_id' => $supplierA->id]);
        $withSupplierB = Equipment::factory()->create(['stock_quantity' => 0, 'cost_price' => 5, 'supplier_id' => $supplierB->id]);
        $withoutSupplier = Equipment::factory()->create(['stock_quantity' => 0, 'cost_price' => 1]);

        Installation::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now(),
            'material_items' => [
                ['equipment_id' => $withSupplierA->id, 'name' => $withSupplierA->name, 'unit' => 'buc', 'quantity' => 2],
                ['equipment_id' => $withSupplierB->id, 'name' => $withSupplierB->name, 'unit' => 'buc', 'quantity' => 4],
                ['equipment_id' => $withoutSupplier->id, 'name' => $withoutSupplier->name, 'unit' => 'buc', 'quantity' => 3],
            ],
        ]);

        $this->actingAs($this->technician())
            ->post(route('technical.requirements.purchase-orders'), ['all' => 1])
            ->assertRedirect(route('technical.purchase-orders.index'));

        $this->assertSame(2, PurchaseOrder::count());

        $orderA = PurchaseOrder::where('supplier_id', $supplierA->id)->with('items')->firstOrFail();
        $orderB = PurchaseOrder::where('supplier_id', $supplierB->id)->with('items')->firstOrFail();

        $this->assertSame('draft', $orderA->status);
        $this->assertCount(1, $orderA->items);
        $this->assertSame($withSupplierA->id, $orderA->items[0]->equipment_id);
        $this->assertEquals(2, $orderA->items[0]->quantity);

        $this->assertCount(1, $orderB->items);
        $this->assertSame($withSupplierB->id, $orderB->items[0]->equipment_id);
        $this->assertEquals(4, $orderB->items[0]->quantity);

        $this->assertSame(0, PurchaseOrderItem::where('equipment_id', $withoutSupplier->id)->count());
    }

    public function test_generating_purchase_orders_reports_when_nothing_has_a_supplier(): void
    {
        $equipment = Equipment::factory()->create(['stock_quantity' => 0]);
        Installation::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now(),
            'material_items' => [['equipment_id' => $equipment->id, 'name' => $equipment->name, 'unit' => 'buc', 'quantity' => 3]],
        ]);

        $this->actingAs($this->technician())
            ->post(route('technical.requirements.purchase-orders'), ['all' => 1])
            ->assertSessionHas('error');

        $this->assertSame(0, PurchaseOrder::count());
    }

    public function test_generating_purchase_orders_requires_equipment_manage_permission(): void
    {
        $support = User::factory()->create();
        $support->assignRole('suport');

        $this->actingAs($support)
            ->post(route('technical.requirements.purchase-orders'), ['all' => 1])
            ->assertForbidden();
    }
}
