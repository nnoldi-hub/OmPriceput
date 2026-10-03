<?php

namespace Tests\Feature\Sales;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\Installation;
use App\Models\Offer;
use App\Models\Service;
use App\Models\User;
use App\Notifications\OfferSent;
use App\Notifications\OfferStatusChanged;
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
}
