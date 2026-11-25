<?php

namespace Tests\Feature\Sales;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\Installation;
use App\Models\Offer;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\OfferSent;
use App\Notifications\OfferStatusChanged;
use App\Services\MaterialRequirementService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OfferCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $salesUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->salesUser = User::factory()->create();
        $this->salesUser->assignRole('vanzari');
    }

    public function test_sales_user_can_create_an_offer_with_items(): void
    {
        $client = Client::factory()->create();
        $equipment = Equipment::factory()->create(['unit_price' => 200]);

        $response = $this->actingAs($this->salesUser)->post(route('sales.offers.store'), [
            'client_id' => $client->id,
            'title' => 'Oferta test',
            'status' => 'draft',
            'job_type' => 'reparatie',
            'items' => [
                ['equipment_id' => $equipment->id, 'description' => $equipment->name, 'quantity' => 3, 'unit_price' => 200],
                ['equipment_id' => null, 'description' => 'Manopera', 'quantity' => 1, 'unit_price' => 150],
            ],
        ]);

        $offer = Offer::firstWhere('title', 'Oferta test');
        $response->assertRedirect(route('sales.offers.show', $offer));

        $this->assertEquals(750, $offer->total_amount);
        $this->assertCount(2, $offer->items);
    }

    public function test_offer_show_displays_estimated_profitability(): void
    {
        $client = Client::factory()->create();
        $equipment = Equipment::factory()->create(['cost_price' => 100, 'unit_price' => 200]);
        $offer = Offer::factory()->create([
            'client_id' => $client->id,
            'user_id' => $this->salesUser->id,
            'total_amount' => 200,
        ]);
        $offer->items()->create([
            'equipment_id' => $equipment->id,
            'description' => $equipment->name,
            'quantity' => 1,
            'unit_price' => 200,
        ]);

        $this->actingAs($this->salesUser)
            ->get(route('sales.offers.show', $offer))
            ->assertInertia(fn ($page) => $page
                ->component('Sales/Offers/Show')
                ->where('profitability.estimated_cost', 100)
                ->where('profitability.estimated_profit', 100)
                ->where('profitability.margin_percent', 50)
            );
    }

    public function test_offer_requires_at_least_one_item(): void
    {
        $client = Client::factory()->create();

        $this->actingAs($this->salesUser)->post(route('sales.offers.store'), [
            'client_id' => $client->id,
            'title' => 'Oferta fara produse',
            'status' => 'draft',
            'items' => [],
        ])->assertSessionHasErrors('items');
    }

    public function test_offer_splits_total_between_materials_and_labor_and_ignores_client_materials(): void
    {
        $client = Client::factory()->create();
        $equipment = Equipment::factory()->create(['unit_price' => 100]);

        $this->actingAs($this->salesUser)->post(route('sales.offers.store'), [
            'client_id' => $client->id,
            'title' => 'Oferta cu sectiuni',
            'status' => 'draft',
            'job_type' => 'instalare',
            'items' => [
                ['section' => 'materials', 'equipment_id' => $equipment->id, 'description' => $equipment->name, 'quantity' => 2, 'unit' => 'buc', 'unit_price' => 100],
                ['section' => 'labor', 'service_id' => null, 'description' => 'Manopera montaj', 'quantity' => 1, 'unit' => 'ora', 'unit_price' => 150],
                ['section' => 'client_materials', 'description' => 'Cablu CYYF 3x2.5', 'quantity' => 10, 'unit' => 'm', 'unit_price' => 999],
            ],
        ]);

        $offer = Offer::firstWhere('title', 'Oferta cu sectiuni');

        $this->assertEquals(350, $offer->total_amount);
        $this->assertEquals(200, $offer->materialsSubtotal());
        $this->assertEquals(150, $offer->laborSubtotal());
        $this->assertEquals(350, $offer->pricedSubtotal());
        $this->assertCount(3, $offer->items);

        $clientMaterial = $offer->items->firstWhere('section', 'client_materials');
        $this->assertNotNull($clientMaterial);
        $this->assertEquals(0, $clientMaterial->unit_price);
        $this->assertEquals('m', $clientMaterial->unit);
    }

    public function test_infers_materials_and_labor_when_section_is_missing(): void
    {
        $client = Client::factory()->create();
        $equipment = Equipment::factory()->create(['unit_price' => 200]);

        $this->actingAs($this->salesUser)->post(route('sales.offers.store'), [
            'client_id' => $client->id,
            'title' => 'Oferta fara sectiune',
            'status' => 'draft',
            'job_type' => 'instalare',
            'items' => [
                ['equipment_id' => $equipment->id, 'description' => $equipment->name, 'quantity' => 1, 'unit_price' => 200],
                ['equipment_id' => null, 'description' => 'Manopera', 'quantity' => 1, 'unit_price' => 100],
            ],
        ]);

        $offer = Offer::firstWhere('title', 'Oferta fara sectiune');

        $this->assertEquals(300, $offer->total_amount);
        $this->assertSame('materials', $offer->items->firstWhere('equipment_id', $equipment->id)->section);
        $this->assertSame('labor', $offer->items->firstWhere('equipment_id', null)->section);
    }

    public function test_sales_user_can_update_offer_status_and_it_promotes_client_on_accept(): void
    {
        $client = Client::factory()->create(['status' => 'lead']);
        $offer = Offer::factory()->create(['client_id' => $client->id, 'user_id' => $this->salesUser->id, 'status' => 'sent']);

        $this->actingAs($this->salesUser)
            ->patch(route('sales.offers.status', $offer), ['status' => 'accepted'])
            ->assertRedirect();

        $this->assertDatabaseHas('offers', ['id' => $offer->id, 'status' => 'accepted']);
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'status' => 'client']);
    }

    public function test_accepting_an_offer_automatically_creates_an_installation(): void
    {
        $client = Client::factory()->create(['address' => 'Str. Lalelelor 5', 'city' => 'Cluj-Napoca']);
        $offer = Offer::factory()->create(['client_id' => $client->id, 'user_id' => $this->salesUser->id, 'status' => 'sent']);

        $this->actingAs($this->salesUser)
            ->patch(route('sales.offers.status', $offer), ['status' => 'accepted']);

        $installation = Installation::firstWhere('offer_id', $offer->id);
        $this->assertNotNull($installation);
        $this->assertEquals('scheduled', $installation->status);
        $this->assertStringContainsString('Lalelelor', $installation->address);
    }

    public function test_accepting_an_offer_imports_materials_and_services_into_installation(): void
    {
        $client = Client::factory()->create();
        $equipment = Equipment::factory()->create(['name' => 'Tablou electric', 'unit' => 'buc']);
        $service = Service::create(['name' => 'Montaj prize', 'unit' => 'ora']);
        $offer = Offer::factory()->create([
            'client_id' => $client->id,
            'user_id' => $this->salesUser->id,
            'status' => 'sent',
        ]);
        $offer->items()->create([
            'equipment_id' => $equipment->id,
            'description' => $equipment->name,
            'quantity' => 4,
        ]);
        $offer->items()->create([
            'service_id' => $service->id,
            'description' => $service->name,
            'quantity' => 1,
        ]);

        $this->actingAs($this->salesUser)
            ->patch(route('sales.offers.status', $offer), ['status' => 'accepted']);

        $installation = Installation::firstWhere('offer_id', $offer->id);
        $this->assertSame(4, $installation->material_items[0]['quantity']);
        $this->assertSame($equipment->id, $installation->material_items[0]['equipment_id']);
        $this->assertSame($service->id, $installation->service_items[0]['service_id']);
    }

    public function test_accepting_an_offer_twice_does_not_create_duplicate_installations(): void
    {
        $client = Client::factory()->create();
        $offer = Offer::factory()->create(['client_id' => $client->id, 'user_id' => $this->salesUser->id, 'status' => 'sent']);

        $this->actingAs($this->salesUser)->patch(route('sales.offers.status', $offer), ['status' => 'rejected']);
        $this->actingAs($this->salesUser)->patch(route('sales.offers.status', $offer), ['status' => 'accepted']);
        $this->actingAs($this->salesUser)->patch(route('sales.offers.status', $offer), ['status' => 'sent']);
        $this->actingAs($this->salesUser)->patch(route('sales.offers.status', $offer), ['status' => 'accepted']);

        $this->assertEquals(1, Installation::where('offer_id', $offer->id)->count());
    }

    public function test_marking_offer_as_sent_notifies_the_client_by_email(): void
    {
        Notification::fake();
        config(['notifications.mail_enabled' => true]);

        $client = Client::factory()->create(['email' => 'client@example.com']);
        $offer = Offer::factory()->create(['client_id' => $client->id, 'user_id' => $this->salesUser->id, 'status' => 'draft']);

        $this->actingAs($this->salesUser)
            ->patch(route('sales.offers.status', $offer), ['status' => 'sent']);

        Notification::assertSentOnDemand(OfferSent::class, function (OfferSent $notification, array $channels, object $notifiable) use ($client, $offer) {
            return $notifiable->routes['mail'] === $client->email
                && $notification->offer->is($offer);
        });
    }

    public function test_offer_sent_notification_is_not_emailed_when_mail_is_disabled(): void
    {
        Notification::fake();
        config(['notifications.mail_enabled' => false]);

        $client = Client::factory()->create(['email' => 'client@example.com']);
        Offer::factory()->create(['client_id' => $client->id, 'user_id' => $this->salesUser->id, 'status' => 'draft']);

        $this->actingAs($this->salesUser)
            ->patch(route('sales.offers.status', $client->offers()->first(), ['status' => 'sent']));

        Notification::assertNothingSent();
    }

    public function test_client_accepting_an_offer_notifies_the_offer_owner_and_creates_installation(): void
    {
        Notification::fake();

        $clientUser = User::factory()->create();
        $clientUser->assignRole('client');
        $client = Client::factory()->create([
            'user_id' => $clientUser->id,
            'address' => 'Str. Testului 10',
            'city' => 'Bucuresti',
        ]);
        $offer = Offer::factory()->create([
            'client_id' => $client->id,
            'user_id' => $this->salesUser->id,
            'status' => 'sent',
        ]);

        $this->actingAs($clientUser)
            ->patch(route('client.offers.status', $offer), [
                'status' => 'accepted',
                'message' => 'Putem programa instalarea.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('offers', ['id' => $offer->id, 'status' => 'accepted']);
        $this->assertDatabaseHas('installations', [
            'offer_id' => $offer->id,
            'client_id' => $client->id,
            'status' => 'scheduled',
        ]);
        Notification::assertSentTo($this->salesUser, OfferStatusChanged::class, function (OfferStatusChanged $notification): bool {
            return $notification->status === 'accepted'
                && $notification->message === 'Putem programa instalarea.';
        });
    }

    public function test_client_rejecting_an_offer_notifies_the_offer_owner_without_creating_installation(): void
    {
        Notification::fake();

        $clientUser = User::factory()->create();
        $clientUser->assignRole('client');
        $client = Client::factory()->create(['user_id' => $clientUser->id]);
        $offer = Offer::factory()->create([
            'client_id' => $client->id,
            'user_id' => $this->salesUser->id,
            'status' => 'sent',
        ]);

        $this->actingAs($clientUser)
            ->patch(route('client.offers.status', $offer), [
                'status' => 'rejected',
                'message' => 'Vom reveni cu o decizie.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('offers', ['id' => $offer->id, 'status' => 'rejected']);
        $this->assertDatabaseCount('installations', 0);
        Notification::assertSentTo($this->salesUser, OfferStatusChanged::class, function (OfferStatusChanged $notification): bool {
            return $notification->status === 'rejected'
                && $notification->message === 'Vom reveni cu o decizie.';
        });
    }

    public function test_offer_pdf_can_be_downloaded(): void
    {
        $client = Client::factory()->create();
        $offer = Offer::factory()->create(['client_id' => $client->id, 'user_id' => $this->salesUser->id]);
        $offer->items()->create(['description' => 'Test item', 'quantity' => 1, 'unit_price' => 100]);

        $this->actingAs($this->salesUser)
            ->get(route('sales.offers.pdf', $offer))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_offers_export_downloads_a_file(): void
    {
        $client = Client::factory()->create();
        Offer::factory()->count(2)->create(['client_id' => $client->id, 'user_id' => $this->salesUser->id]);

        $this->actingAs($this->salesUser)
            ->get(route('sales.offers.export'))
            ->assertOk();
    }

    public function test_offer_pipeline_summary_is_included_on_index(): void
    {
        $client = Client::factory()->create();
        Offer::factory()->create(['client_id' => $client->id, 'user_id' => $this->salesUser->id, 'status' => 'draft', 'total_amount' => 100]);
        Offer::factory()->create(['client_id' => $client->id, 'user_id' => $this->salesUser->id, 'status' => 'accepted', 'total_amount' => 500]);

        $response = $this->actingAs($this->salesUser)->get(route('sales.offers.index'));

        $response->assertInertia(fn ($page) => $page
            ->component('Sales/Offers/Index')
            ->where('pipeline.draft.total', 1)
            ->where('pipeline.accepted.total', 1)
        );
    }

    public function test_free_text_material_can_be_added_to_the_catalog(): void
    {
        $client = Client::factory()->create();
        $offer = Offer::factory()->create(['client_id' => $client->id, 'user_id' => $this->salesUser->id]);
        $item = $offer->items()->create([
            'section' => 'materials',
            'description' => 'Diblu special 12',
            'quantity' => 4,
            'unit' => 'buc',
            'unit_price' => 3,
        ]);

        $this->actingAs($this->salesUser)
            ->post(route('sales.offers.items.catalog', [$offer, $item]))
            ->assertRedirect();

        $equipment = Equipment::firstWhere('name', 'Diblu special 12');
        $this->assertNotNull($equipment);
        $this->assertSame('buc', $equipment->unit);
        $this->assertEquals(3, $equipment->unit_price);
        $this->assertTrue($equipment->is_active);
        $this->assertSame($equipment->id, $item->fresh()->equipment_id);
    }

    public function test_adding_a_material_to_catalog_links_identical_lines_in_the_offer(): void
    {
        $client = Client::factory()->create();
        $offer = Offer::factory()->create(['client_id' => $client->id, 'user_id' => $this->salesUser->id]);
        $first = $offer->items()->create(['section' => 'materials', 'description' => 'Priza dubla', 'quantity' => 2, 'unit' => 'buc', 'unit_price' => 15]);
        $second = $offer->items()->create(['section' => 'materials', 'description' => 'Priza dubla', 'quantity' => 3, 'unit' => 'buc', 'unit_price' => 15]);

        $this->actingAs($this->salesUser)
            ->post(route('sales.offers.items.catalog', [$offer, $first]))
            ->assertRedirect();

        $this->assertSame(1, Equipment::where('name', 'Priza dubla')->count());
        $equipment = Equipment::firstWhere('name', 'Priza dubla');
        $this->assertSame($equipment->id, $first->fresh()->equipment_id);
        $this->assertSame($equipment->id, $second->fresh()->equipment_id);
    }

    public function test_already_linked_material_is_not_added_to_catalog_again(): void
    {
        $client = Client::factory()->create();
        $offer = Offer::factory()->create(['client_id' => $client->id, 'user_id' => $this->salesUser->id]);
        $item = $offer->items()->create(['section' => 'materials', 'description' => 'Cablu 3x2.5', 'quantity' => 5, 'unit' => 'm', 'unit_price' => 10]);

        $this->actingAs($this->salesUser)->post(route('sales.offers.items.catalog', [$offer, $item]));
        $this->actingAs($this->salesUser)->post(route('sales.offers.items.catalog', [$offer, $item]))->assertRedirect();

        $this->assertSame(1, Equipment::where('name', 'Cablu 3x2.5')->count());
    }

    public function test_quick_created_items_are_saved_to_catalog_only_on_offer_save_and_deduplicated(): void
    {
        $client = Client::factory()->create();

        // Simulează abandonarea formularului: nicio linie nu ajunge în catalog.
        $this->actingAs($this->salesUser)->get(route('sales.offers.create'))->assertOk();
        $this->assertSame(0, Equipment::count());
        $this->assertSame(0, Service::count());

        $this->actingAs($this->salesUser)->post(route('sales.offers.store'), [
            'client_id' => $client->id,
            'title' => 'Oferta cu articole rapide',
            'status' => 'draft',
            'job_type' => 'instalare',
            'chapters' => [
                ['ref' => 'c1', 'id' => null, 'name' => 'Instalatii electrice', 'position' => 0],
            ],
            'items' => [
                [
                    'chapter_ref' => 'c1', 'section' => 'materials', 'description' => 'Șurub special 12',
                    'quantity' => 2, 'unit' => 'buc', 'unit_price' => 5, 'unit_cost' => 2.5, 'save_to_catalog' => true,
                ],
                [
                    'chapter_ref' => 'c1', 'section' => 'materials', 'description' => 'surub special 12',
                    'quantity' => 3, 'unit' => 'buc', 'unit_price' => 5, 'unit_cost' => 2.5, 'save_to_catalog' => true,
                ],
                [
                    'section' => 'labor', 'description' => 'Montaj special',
                    'quantity' => 1, 'unit' => 'ora', 'unit_price' => 100, 'unit_cost' => 40, 'save_to_catalog' => true,
                ],
                [
                    'section' => 'materials', 'description' => 'Material ne salvat',
                    'quantity' => 1, 'unit' => 'buc', 'unit_price' => 7, 'save_to_catalog' => false,
                ],
            ],
        ])->assertRedirect();

        $offer = Offer::firstWhere('title', 'Oferta cu articole rapide');
        $chapter = $offer->chapters()->firstOrFail();
        $this->assertSame('Instalatii electrice', $chapter->name);

        $this->assertSame(1, Equipment::where('name', 'Șurub special 12')->count());
        $this->assertSame(1, Service::where('name', 'Montaj special')->count());
        $this->assertSame(0, Equipment::where('name', 'Material ne salvat')->count());

        $materials = $offer->items->where('section', 'materials');
        $equipment = Equipment::firstWhere('name', 'Șurub special 12');
        $this->assertSame($equipment->id, $materials->first()->equipment_id);
        $this->assertSame($equipment->id, $materials->get(1)->equipment_id);
        $this->assertSame($chapter->id, $materials->first()->chapter_id);
        $this->assertEquals(2.5, $materials->first()->unit_cost);
        $this->assertSame($offer->items->firstWhere('section', 'labor')->service_id, Service::firstWhere('name', 'Montaj special')->id);
    }

    public function test_summary_by_chapter_uses_current_lines_and_vat_setting(): void
    {
        Setting::set('vat_percentage', '20');
        $client = Client::factory()->create();
        $offer = Offer::factory()->create(['client_id' => $client->id, 'user_id' => $this->salesUser->id]);
        $chapter = $offer->chapters()->create(['name' => 'Electrice', 'position' => 1]);

        $offer->items()->create([
            'chapter_id' => $chapter->id, 'section' => 'materials', 'description' => 'Cablu',
            'quantity' => 2, 'unit_price' => 100, 'unit_cost' => 40,
        ]);
        $offer->items()->create([
            'chapter_id' => $chapter->id, 'section' => 'labor', 'description' => 'Montaj',
            'quantity' => 1, 'unit_price' => 200, 'unit_cost' => 80,
        ]);
        $offer->items()->create([
            'section' => 'client_materials', 'description' => 'Material client',
            'quantity' => 10, 'unit_price' => 999,
        ]);

        $summary = $offer->summaryByChapter();

        $this->assertCount(1, $summary['chapters']);
        $this->assertSame('Electrice', $summary['chapters'][0]['name']);
        $this->assertSame(200.0, $summary['materials']);
        $this->assertSame(200.0, $summary['labor']);
        $this->assertSame(400.0, $summary['subtotal']);
        $this->assertTrue($summary['is_vat_payer']);
        $this->assertSame(80.0, $summary['vat_amount']);
        $this->assertSame(480.0, $summary['total_with_vat']);
    }

    public function test_summary_skips_vat_when_company_is_not_a_vat_payer(): void
    {
        Setting::set('vat_percentage', '0');
        $offer = Offer::factory()->create(['user_id' => $this->salesUser->id]);
        $offer->items()->create(['section' => 'labor', 'description' => 'Manopera', 'quantity' => 2, 'unit_price' => 75]);

        $summary = $offer->summaryByChapter();

        $this->assertFalse($summary['is_vat_payer']);
        $this->assertSame(0.0, $summary['vat_amount']);
        $this->assertSame(150.0, $summary['total_with_vat']);
    }

    public function test_offer_can_be_created_without_vat_and_pdf_specifies_the_regime(): void
    {
        Setting::set('vat_percentage', '20');
        $client = Client::factory()->create();

        $this->actingAs($this->salesUser)->post(route('sales.offers.store'), [
            'client_id' => $client->id,
            'title' => 'Oferta fara TVA',
            'status' => 'draft',
            'job_type' => 'reparatie',
            'vat_applied' => false,
            'items' => [
                ['description' => 'Manopera', 'quantity' => 2, 'unit_price' => 100],
            ],
        ])->assertRedirect();

        $offer = Offer::firstWhere('title', 'Oferta fara TVA');
        $this->assertFalse($offer->vat_applied);

        $summary = $offer->summaryByChapter();
        $this->assertFalse($summary['vat_applied']);
        $this->assertSame(0.0, $summary['vat_percentage']);
        $this->assertFalse($summary['is_vat_payer']);
        $this->assertSame(0.0, $summary['vat_amount']);
        $this->assertSame(200.0, $summary['total_with_vat']);

        $html = view('pdfs.offer', ['offer' => $offer->load('client', 'chapters', 'items')])->render();
        $this->assertStringContainsString('fără TVA', $html);
        $this->assertStringContainsString('Total (fără TVA)', $html);
        $this->assertStringNotContainsString('TVA (20%)', $html);
    }

    public function test_offer_defaults_to_vat_applied_and_show_exposes_the_regime(): void
    {
        Setting::set('vat_percentage', '20');
        $offer = Offer::factory()->create(['user_id' => $this->salesUser->id]);
        $offer->items()->create(['section' => 'labor', 'description' => 'Manopera', 'quantity' => 1, 'unit_price' => 100]);

        $this->assertTrue($offer->vat_applied);
        $summary = $offer->summaryByChapter();
        $this->assertTrue($summary['vat_applied']);
        $this->assertTrue($summary['is_vat_payer']);
        $this->assertSame(20.0, $summary['vat_percentage']);
        $this->assertSame(120.0, $summary['total_with_vat']);

        $this->actingAs($this->salesUser)
            ->get(route('sales.offers.show', $offer))
            ->assertInertia(fn ($page) => $page
                ->component('Sales/Offers/Show')
                ->where('summary.vat_applied', true)
                ->where('summary.is_vat_payer', true)
            );
    }

    public function test_editing_an_offer_can_switch_to_no_vat(): void
    {
        Setting::set('vat_percentage', '20');
        $client = Client::factory()->create();
        $offer = Offer::factory()->create(['client_id' => $client->id, 'user_id' => $this->salesUser->id]);
        $offer->items()->create(['section' => 'labor', 'description' => 'Manopera', 'quantity' => 1, 'unit_price' => 100]);

        $this->actingAs($this->salesUser)->put(route('sales.offers.update', $offer), [
            'client_id' => $client->id,
            'title' => $offer->title,
            'status' => 'draft',
            'job_type' => $offer->job_type ?? 'instalare',
            'vat_applied' => false,
            'items' => [
                ['section' => 'labor', 'description' => 'Manopera', 'quantity' => 1, 'unit_price' => 100],
            ],
        ])->assertRedirect();

        $offer->refresh();
        $this->assertFalse($offer->vat_applied);
        $this->assertSame(0.0, $offer->summaryByChapter()['vat_amount']);
    }

    public function test_legacy_offer_without_chapters_uses_general_chapter(): void
    {
        $offer = Offer::factory()->create(['user_id' => $this->salesUser->id]);
        $offer->items()->create(['section' => 'materials', 'description' => 'Material vechi', 'quantity' => 4, 'unit_price' => 25]);

        $summary = $offer->summaryByChapter();

        $this->assertCount(1, $summary['chapters']);
        $this->assertNull($summary['chapters'][0]['id']);
        $this->assertSame('General', $summary['chapters'][0]['name']);
        $this->assertSame(100.0, $summary['chapters'][0]['total']);

        $this->actingAs($this->salesUser)
            ->get(route('sales.offers.show', $offer))
            ->assertInertia(fn ($page) => $page->where('summary.chapters.0.name', 'General'));
    }

    public function test_valid_until_uses_configured_days_from_issue_date_and_is_visible_in_pdf(): void
    {
        Setting::set('offer_validity_days', '10');
        $this->assertSame(10, Offer::validityDays());

        $client = Client::factory()->create();
        $this->actingAs($this->salesUser)->post(route('sales.offers.store'), [
            'client_id' => $client->id,
            'title' => 'Oferta cu valabilitate configurata',
            'status' => 'draft',
            'job_type' => 'instalare',
            'items' => [
                ['section' => 'labor', 'description' => 'Montaj', 'quantity' => 1, 'unit_price' => 100],
            ],
        ])->assertRedirect();

        $offer = Offer::firstWhere('title', 'Oferta cu valabilitate configurata');
        $expected = $offer->created_at->copy()->startOfDay()->addDays(10)->toDateString();

        $this->assertSame($expected, $offer->valid_until->toDateString());
        $this->assertSame($expected, $offer->validUntilDate()->toDateString());

        // Verifică șablonul PDF: fluxul PDF este comprimat, deci data se caută în HTML.
        $html = view('pdfs.offer', ['offer' => $offer->load('client', 'chapters', 'items')])->render();
        $this->assertStringContainsString($offer->validUntilDate()->format('d.m.Y'), $html);
    }

    public function test_profitability_uses_line_cost_without_adding_catalog_cost(): void
    {
        $equipment = Equipment::factory()->create(['cost_price' => 100, 'unit_price' => 200]);
        $offer = Offer::factory()->create(['user_id' => $this->salesUser->id, 'total_amount' => 300]);
        $item = $offer->items()->create([
            'equipment_id' => $equipment->id, 'section' => 'materials', 'description' => $equipment->name,
            'quantity' => 2, 'unit_price' => 150, 'unit_cost' => 25,
        ]);

        $this->assertSame(50.0, $item->lineCost());
        $this->assertSame(83.33, $item->marginPercent());

        $report = $offer->profitability_report;
        $this->assertSame(50.0, $report['estimated_cost']);
        $this->assertSame(250.0, $report['estimated_profit']);
        $this->assertSame(83.33, $report['margin_percent']);
    }

    public function test_client_offer_view_does_not_expose_costs_or_margins(): void
    {
        $clientUser = User::factory()->create();
        $clientUser->assignRole('client');
        $client = Client::factory()->create(['user_id' => $clientUser->id]);
        $offer = Offer::factory()->create([
            'client_id' => $client->id, 'user_id' => $this->salesUser->id, 'status' => 'sent',
        ]);
        $offer->items()->create([
            'section' => 'materials', 'description' => 'Material cu cost',
            'quantity' => 1, 'unit_price' => 100, 'unit_cost' => 60,
        ]);

        $this->actingAs($clientUser)
            ->get(route('client.offers.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Client/Offers/Index')
                ->where('offers.0.items.0.description', 'Material cu cost')
                ->missing('offers.0.items.0.unit_cost')
                ->missing('offers.0.profitability_report')
            );
    }

    public function test_editing_an_accepted_offer_refreshes_installation_material_requirement(): void
    {
        $client = Client::factory()->create();
        $oldEquipment = Equipment::factory()->create(['stock_quantity' => 0, 'name' => 'Material vechi']);
        $offer = Offer::factory()->create([
            'client_id' => $client->id, 'user_id' => $this->salesUser->id, 'status' => 'accepted',
        ]);
        $offer->items()->create([
            'equipment_id' => $oldEquipment->id, 'section' => 'materials', 'description' => $oldEquipment->name,
            'quantity' => 2, 'unit_price' => 10,
        ]);
        $installation = Installation::factory()->create([
            'offer_id' => $offer->id, 'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
            'material_items' => [['equipment_id' => $oldEquipment->id, 'name' => $oldEquipment->name, 'unit' => 'buc', 'quantity' => 2]],
        ]);

        $this->actingAs($this->salesUser)->put(route('sales.offers.update', $offer), [
            'client_id' => $client->id,
            'title' => $offer->title,
            'status' => 'accepted',
            'job_type' => 'instalare',
            'valid_until' => $offer->valid_until->toDateString(),
            'items' => [
                ['section' => 'materials', 'description' => 'Copex 20', 'quantity' => 7, 'unit' => 'm', 'unit_price' => 1.5],
            ],
        ])->assertRedirect();

        $this->assertSame('Copex 20', $installation->fresh()->material_items[0]['name']);
        $this->assertSame(7, $installation->fresh()->material_items[0]['quantity']);

        $names = collect(app(MaterialRequirementService::class)->build()['rows'])->pluck('name')->all();
        $this->assertContains('Copex 20', $names);
        $this->assertNotContains('Material vechi', $names);
    }
}
