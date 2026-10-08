<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Equipment;
use App\Models\Setting;
use App\Models\ShopOrder;
use App\Notifications\NewShopOrderReceived;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureShopEnabled();

        $products = Equipment::visibleInShop()
            ->when($request->string('category')->toString(), fn ($query, $category) => $query->where('category', $category))
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Equipment $item) => $this->presentProduct($item));

        return Inertia::render('Public/Shop/Index', [
            'products' => $products,
            'filters' => $request->only('category', 'search'),
            'categories' => Equipment::CATEGORIES,
        ]);
    }

    public function show(string $slug): Response
    {
        $this->ensureShopEnabled();

        $equipment = Equipment::visibleInShop()->where('slug', $slug)->firstOrFail();

        $this->applySeo($equipment);

        return Inertia::render('Public/Shop/Show', [
            'product' => $this->presentProduct($equipment),
        ]);
    }

    private function applySeo(Equipment $equipment): void
    {
        $description = $equipment->shop_description ?: $equipment->description ?: $equipment->name;

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $equipment->name,
            'description' => $description,
            'url' => route('public.shop.show', $equipment->slug),
            'brand' => ['@type' => 'Brand', 'name' => config('app.name')],
            'offers' => [
                '@type' => 'Offer',
                'price' => $equipment->shop_price,
                'priceCurrency' => 'RON',
                'availability' => $equipment->stock_quantity > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'url' => route('public.shop.show', $equipment->slug),
            ],
        ];

        if ($equipment->sku) {
            $schema['sku'] = $equipment->sku;
        }

        if ($equipment->image_path) {
            $schema['image'] = asset('storage/'.$equipment->image_path);
        }

        app(Seo::class)
            ->title($equipment->name)
            ->description($description)
            ->allowIndex()
            ->jsonLd([$schema]);
    }

    public function cart(Request $request): Response
    {
        $this->ensureShopEnabled();

        $client = $request->user()?->clientProfile;

        return Inertia::render('Public/Shop/Cart', [
            'prefill' => $client ? [
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'shipping_address' => $client->address,
                'shipping_city' => $client->city,
            ] : null,
            'freeShippingThreshold' => (float) Setting::get('shop_free_shipping_threshold', '500'),
            'shippingCost' => (float) Setting::get('shop_shipping_cost', '25'),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $this->ensureShopEnabled();

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.equipment_id' => ['required', 'integer', 'exists:equipment,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'shipping_address' => ['required', 'string', 'max:255'],
            'shipping_city' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'wants_installation' => ['nullable', 'boolean'],
            'payment_method' => ['required', 'in:cod,transfer'],
        ]);

        $order = DB::transaction(function () use ($data, $request) {
            $subtotal = 0.0;
            $discountTotal = 0.0;
            $lineData = [];

            foreach ($data['items'] as $line) {
                $equipment = Equipment::visibleInShop()->lockForUpdate()->findOrFail($line['equipment_id']);
                $quantity = (int) $line['quantity'];

                abort_if($equipment->stock_quantity < $quantity, 422, "Stoc insuficient pentru {$equipment->name}.");

                $unitPrice = (float) $equipment->unit_price;
                $shopPrice = $equipment->shop_price;
                $discountPerUnit = round($unitPrice - $shopPrice, 2);

                $subtotal += round($unitPrice * $quantity, 2);
                $discountTotal += round($discountPerUnit * $quantity, 2);

                $lineData[] = [
                    'equipment' => $equipment,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discountPerUnit,
                    'line_total' => round($shopPrice * $quantity, 2),
                ];
            }

            $netSubtotal = round($subtotal - $discountTotal, 2);
            $freeShippingThreshold = (float) Setting::get('shop_free_shipping_threshold', '500');
            $shippingCost = $netSubtotal >= $freeShippingThreshold ? 0 : (float) Setting::get('shop_shipping_cost', '25');
            $total = round($netSubtotal + $shippingCost, 2);

            $client = $this->resolveClient($request, $data);

            $order = ShopOrder::create([
                'order_number' => ShopOrder::nextOrderNumber(),
                'client_id' => $client?->id,
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'],
                'shipping_address' => $data['shipping_address'],
                'shipping_city' => $data['shipping_city'] ?? null,
                'notes' => $data['notes'] ?? null,
                'wants_installation' => $request->boolean('wants_installation'),
                'payment_method' => $data['payment_method'],
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'status' => 'new',
            ]);

            foreach ($lineData as $line) {
                $order->items()->create([
                    'equipment_id' => $line['equipment']->id,
                    'name' => $line['equipment']->name,
                    'unit_price' => $line['unit_price'],
                    'discount_amount' => $line['discount_amount'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);

                $line['equipment']->decrement('stock_quantity', $line['quantity']);
            }

            return $order;
        });

        $recipients = Role::findByName('admin')->users()->get();
        Notification::send($recipients, new NewShopOrderReceived($order));

        return redirect()->route('public.shop.confirmation', $order->order_number)
            ->with('success', 'Comanda a fost inregistrata. Va multumim!');
    }

    public function confirmation(string $orderNumber): Response
    {
        $order = ShopOrder::where('order_number', $orderNumber)->with('items')->firstOrFail();

        return Inertia::render('Public/Shop/Confirmation', ['order' => $order]);
    }

    private function resolveClient(Request $request, array $data): ?Client
    {
        $user = $request->user();

        if ($user?->clientProfile) {
            return $user->clientProfile;
        }

        $client = Client::where('phone', $data['phone'])
            ->when($data['email'] ?? null, fn ($query, $email) => $query->orWhere('email', $email))
            ->first();

        return $client ?? Client::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'city' => $data['shipping_city'] ?? null,
            'address' => $data['shipping_address'] ?? null,
            'source' => 'web',
            'status' => 'lead',
        ]);
    }

    private function presentProduct(Equipment $equipment): array
    {
        return [
            'id' => $equipment->id,
            'name' => $equipment->name,
            'slug' => $equipment->slug,
            'category' => $equipment->category,
            'category_label' => Equipment::CATEGORIES[$equipment->category] ?? $equipment->category,
            'unit' => $equipment->unit,
            'unit_price' => (float) $equipment->unit_price,
            'shop_price' => $equipment->shop_price,
            'shop_discount_amount' => $equipment->shop_discount_amount,
            'image_path' => $equipment->image_path,
            'shop_description' => $equipment->shop_description,
            'description' => $equipment->description,
            'stock_quantity' => $equipment->stock_quantity,
            'in_stock' => $equipment->stock_quantity > 0,
        ];
    }

    private function ensureShopEnabled(): void
    {
        abort_unless(Setting::get('shop_enabled', '0') === '1', 404);
    }
}
