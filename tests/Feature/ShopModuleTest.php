<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Discount;
use App\Models\Equipment;
use App\Models\Setting;
use App\Models\ShopOrder;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ShopModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Setting::query()->delete();
        Setting::set('shop_enabled', '1');
    }

    public function test_shop_returns_404_when_disabled(): void
    {
        Setting::set('shop_enabled', '0');
        Equipment::factory()->create(['is_visible_in_shop' => true, 'slug' => 'produs-test']);

        $this->get(route('public.shop.index'))->assertNotFound();
    }

    public function test_shop_index_only_lists_visible_active_products(): void
    {
        $visible = Equipment::factory()->create(['is_visible_in_shop' => true, 'is_active' => true, 'slug' => 'vizibil']);
        Equipment::factory()->create(['is_visible_in_shop' => false, 'is_active' => true, 'slug' => 'ascuns']);
        Equipment::factory()->create(['is_visible_in_shop' => true, 'is_active' => false, 'slug' => 'inactiv']);

        $response = $this->get(route('public.shop.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/Shop/Index')
            ->has('products.data', 1)
            ->where('products.data.0.id', $visible->id)
        );
    }

    public function test_shop_show_returns_product_with_discounted_price(): void
    {
        $equipment = Equipment::factory()->create([
            'is_visible_in_shop' => true,
            'is_active' => true,
            'slug' => 'camera-discount',
            'unit_price' => 100,
        ]);

        Discount::factory()->create([
            'scope' => 'product',
            'equipment_id' => $equipment->id,
            'type' => 'percent',
            'value' => 20,
            'is_active' => true,
        ]);

        $response = $this->get(route('public.shop.show', 'camera-discount'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/Shop/Show')
            ->where('product.shop_price', fn ($value) => (float) $value === 80.0)
        );
    }

    public function test_checkout_creates_order_client_and_decrements_stock(): void
    {
        Notification::fake();

        $equipment = Equipment::factory()->create([
            'is_visible_in_shop' => true,
            'is_active' => true,
            'unit_price' => 100,
            'stock_quantity' => 10,
        ]);

        Setting::set('shop_free_shipping_threshold', '500');
        Setting::set('shop_shipping_cost', '25');

        $response = $this->post(route('public.shop.checkout'), [
            'items' => [
                ['equipment_id' => $equipment->id, 'quantity' => 2],
            ],
            'name' => 'Ion Popescu',
            'email' => 'ion@example.com',
            'phone' => '0712345678',
            'shipping_address' => 'Str. Exemplu nr. 1',
            'shipping_city' => 'Bucuresti',
            'payment_method' => 'cod',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('shop_orders', [
            'name' => 'Ion Popescu',
            'phone' => '0712345678',
            'shipping_cost' => 25,
            'total' => 225,
        ]);

        $this->assertDatabaseHas('clients', ['phone' => '0712345678']);
        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'stock_quantity' => 8]);
    }

    public function test_checkout_applies_free_shipping_above_threshold(): void
    {
        Notification::fake();

        $equipment = Equipment::factory()->create([
            'is_visible_in_shop' => true,
            'is_active' => true,
            'unit_price' => 300,
            'stock_quantity' => 10,
        ]);

        Setting::set('shop_free_shipping_threshold', '500');
        Setting::set('shop_shipping_cost', '25');

        $this->post(route('public.shop.checkout'), [
            'items' => [
                ['equipment_id' => $equipment->id, 'quantity' => 2],
            ],
            'name' => 'Maria Ionescu',
            'phone' => '0723456789',
            'shipping_address' => 'Str. Test nr. 2',
            'payment_method' => 'transfer',
        ])->assertRedirect();

        $this->assertDatabaseHas('shop_orders', [
            'phone' => '0723456789',
            'shipping_cost' => 0,
            'total' => 600,
        ]);
    }

    public function test_checkout_fails_when_stock_insufficient(): void
    {
        $equipment = Equipment::factory()->create([
            'is_visible_in_shop' => true,
            'is_active' => true,
            'stock_quantity' => 1,
        ]);

        $this->post(route('public.shop.checkout'), [
            'items' => [
                ['equipment_id' => $equipment->id, 'quantity' => 5],
            ],
            'name' => 'Test Stoc',
            'phone' => '0700000000',
            'shipping_address' => 'Str. Stoc nr. 3',
            'payment_method' => 'cod',
        ])->assertStatus(422);

        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'stock_quantity' => 1]);
        $this->assertDatabaseCount('shop_orders', 0);
    }

    public function test_admin_can_list_shop_orders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        ShopOrder::factory()->count(2)->create();

        $this->actingAs($admin)
            ->get(route('admin.shop-orders.index'))
            ->assertOk();
    }

    public function test_non_admin_cannot_access_shop_orders(): void
    {
        $tech = User::factory()->create();
        $tech->assignRole('tehnic');

        $this->actingAs($tech)
            ->get(route('admin.shop-orders.index'))
            ->assertForbidden();
    }

    public function test_admin_can_apply_manual_discount_to_order(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $order = ShopOrder::factory()->create([
            'subtotal' => 200,
            'discount_total' => 0,
            'manual_discount' => 0,
            'shipping_cost' => 25,
            'total' => 225,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.shop-orders.discount', $order), ['manual_discount' => 50])
            ->assertRedirect();

        $this->assertDatabaseHas('shop_orders', [
            'id' => $order->id,
            'manual_discount' => 50,
            'total' => 175,
        ]);
    }

    public function test_completing_order_with_client_generates_invoice_automatically(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();
        $order = ShopOrder::factory()->create(['client_id' => $client->id]);

        $this->actingAs($admin)
            ->patch(route('admin.shop-orders.status', $order), ['status' => 'completed'])
            ->assertRedirect();

        $order->refresh();
        $this->assertNotNull($order->invoice_id);
        $this->assertDatabaseHas('invoices', ['id' => $order->invoice_id, 'client_id' => $client->id]);
    }

    public function test_admin_can_create_and_update_discount(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $equipment = Equipment::factory()->create();

        $this->actingAs($admin)->post(route('admin.discounts.store'), [
            'name' => 'Reducere test',
            'scope' => 'product',
            'equipment_id' => $equipment->id,
            'type' => 'percent',
            'value' => 15,
            'is_active' => true,
        ])->assertRedirect(route('admin.discounts.index'));

        $this->assertDatabaseHas('discounts', ['name' => 'Reducere test', 'value' => 15]);

        $discount = Discount::first();

        $this->actingAs($admin)->put(route('admin.discounts.update', $discount), [
            'name' => 'Reducere actualizata',
            'scope' => 'category',
            'category' => $equipment->category,
            'type' => 'fixed',
            'value' => 20,
            'is_active' => false,
        ])->assertRedirect(route('admin.discounts.index'));

        $this->assertDatabaseHas('discounts', ['id' => $discount->id, 'name' => 'Reducere actualizata', 'is_active' => false]);
    }
}
