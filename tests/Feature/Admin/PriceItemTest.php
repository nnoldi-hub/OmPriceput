<?php

namespace Tests\Feature\Admin;

use App\Models\PriceItem;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceItemTest extends TestCase
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

    public function test_non_admin_cannot_access_nomenclator(): void
    {
        $sales = User::factory()->create();
        $sales->assignRole('vanzari');

        $this->actingAs($sales)->get(route('admin.pricing.index'))->assertForbidden();
    }

    public function test_index_lists_price_items(): void
    {
        PriceItem::factory()->create(['name' => 'Priza simpla', 'object_type' => 'priza', 'unit' => 'buc', 'price' => 25]);

        $this->actingAs($this->admin)
            ->get(route('admin.pricing.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Pricing/Index')
                ->has('priceItems', 1)
                ->where('priceItems.0.name', 'Priza simpla')
            );
    }

    public function test_price_item_can_be_created_updated_and_deleted(): void
    {
        $this->actingAs($this->admin)->post(route('admin.pricing.store'), [
            'object_type' => 'suport',
            'name' => 'Suport TV',
            'unit' => 'buc',
            'price' => 45,
            'is_active' => true,
        ])->assertRedirect();

        $item = PriceItem::first();
        $this->assertSame('suport', $item->object_type);
        $this->assertSame(45.0, (float) $item->price);

        $this->actingAs($this->admin)->put(route('admin.pricing.update', $item), [
            'object_type' => 'suport',
            'name' => 'Suport TV reglabil',
            'unit' => 'buc',
            'price' => 60,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('price_items', ['id' => $item->id, 'name' => 'Suport TV reglabil', 'price' => 60]);

        $this->actingAs($this->admin)->delete(route('admin.pricing.destroy', $item))->assertRedirect();
        $this->assertDatabaseMissing('price_items', ['id' => $item->id]);
    }

    public function test_price_item_requires_valid_object_type(): void
    {
        $this->actingAs($this->admin)->post(route('admin.pricing.store'), [
            'object_type' => 'inexistent',
            'name' => 'X',
            'unit' => 'buc',
            'price' => 10,
        ])->assertSessionHasErrors('object_type');
    }
}
