<?php

namespace Tests\Feature\Technical;

use App\Models\Equipment;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EquipmentCrudTest extends TestCase
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

    public function test_sales_user_cannot_access_technical_module(): void
    {
        $sales = User::factory()->create();
        $sales->assignRole('vanzari');

        $this->actingAs($sales)->get(route('technical.equipment.index'))->assertForbidden();
    }

    public function test_technician_can_list_equipment(): void
    {
        Equipment::factory()->count(3)->create();

        $this->actingAs($this->techUser)
            ->get(route('technical.equipment.index'))
            ->assertOk();
    }

    public function test_technician_can_create_equipment(): void
    {
        $this->actingAs($this->techUser)->post(route('technical.equipment.store'), [
            'name' => 'Tablou electric completat',
            'category' => 'instalatii',
            'unit' => 'buc',
            'unit_price' => 350,
            'stock_quantity' => 10,
        ])->assertRedirect(route('technical.equipment.index'));

        $this->assertDatabaseHas('equipment', ['name' => 'Tablou electric completat']);
    }

    public function test_technician_can_adjust_stock(): void
    {
        $equipment = Equipment::factory()->create(['stock_quantity' => 5]);

        $this->actingAs($this->techUser)
            ->patch(route('technical.equipment.stock', $equipment), ['delta' => -2])
            ->assertRedirect();

        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'stock_quantity' => 3]);
    }

    public function test_stock_cannot_go_below_zero(): void
    {
        $equipment = Equipment::factory()->create(['stock_quantity' => 1]);

        $this->actingAs($this->techUser)
            ->patch(route('technical.equipment.stock', $equipment), ['delta' => -5]);

        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'stock_quantity' => 0]);
    }

    public function test_low_stock_filter_returns_only_low_stock_items(): void
    {
        Equipment::factory()->create(['stock_quantity' => 2]);
        Equipment::factory()->create(['stock_quantity' => 50]);

        $response = $this->actingAs($this->techUser)
            ->get(route('technical.equipment.index', ['low_stock' => true]));

        $response->assertInertia(fn ($page) => $page
            ->component('Technical/Equipment/Index')
            ->has('equipment.data', 1)
        );
    }

    public function test_low_stock_filter_uses_each_equipment_minimum_stock(): void
    {
        Equipment::factory()->create(['stock_quantity' => 4, 'minimum_stock' => 4]);
        Equipment::factory()->create(['stock_quantity' => 4, 'minimum_stock' => 3]);

        $response = $this->actingAs($this->techUser)
            ->get(route('technical.equipment.index', ['low_stock' => true]));

        $response->assertInertia(fn ($page) => $page
            ->component('Technical/Equipment/Index')
            ->has('equipment.data', 1)
        );
    }

    public function test_technician_can_import_supplier_catalog_with_markup(): void
    {
        $supplier = Supplier::create(['name' => 'Furnizor test']);
        $file = UploadedFile::fake()->createWithContent(
            'catalog.csv',
            "name,cost_price,sku,category,unit,stock\nCamera 4K,100,CAM-4K,camera,buc,7\n",
        );

        $this->actingAs($this->techUser)
            ->post(route('technical.suppliers.import'), [
                'supplier_id' => $supplier->id,
                'file' => $file,
                'markup_percent' => 30,
                'name_column' => 'name',
                'cost_column' => 'cost_price',
                'sku_column' => 'sku',
                'category_column' => 'category',
                'unit_column' => 'unit',
                'stock_column' => 'stock',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('equipment', [
            'supplier_id' => $supplier->id,
            'sku' => 'CAM-4K',
            'cost_price' => 100,
            'unit_price' => 130,
            'markup_percent' => 30,
        ]);
    }
}
