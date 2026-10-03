<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\Expense;
use App\Models\Supplier;
use App\Models\Installation;
use App\Models\Invoice;
use App\Models\Offer;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
    }

    public function test_reports_page_loads(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.reports'))
            ->assertOk();
    }

    public function test_reports_include_expense_analytics(): void
    {
        $supplier = Supplier::create(['name' => 'Furnizor raport']);
        Expense::create([
            'supplier_id' => $supplier->id,
            'description' => 'Material raport',
            'category' => 'material',
            'amount' => 320,
            'expense_date' => '2026-09-10',
        ]);

        $this->actingAs($this->adminUser)
            ->get(route('admin.reports'))
            ->assertInertia(fn ($page) => $page
                ->where('financial.expensesTotal', 320)
                ->where('financial.expensesByCategory.material', 320)
                ->where('financial.expensesBySupplier.Furnizor raport', 320)
            );
    }

    public function test_sms_logs_page_loads(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.sms-logs'))
            ->assertOk();
    }

    public function test_dashboard_shows_low_stock_alert(): void
    {
        Equipment::factory()->create(['stock_quantity' => 1]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('alerts', 1)
        );
    }

    public function test_dashboard_shows_overdue_invoice_alert(): void
    {
        Invoice::factory()->create(['status' => 'unpaid', 'due_at' => now()->subDays(3)]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('alerts', 1)
        );
    }

    public function test_dashboard_has_no_alerts_when_everything_is_healthy(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('alerts', 0)
        );
    }

    public function test_dashboard_surfaces_actionable_items_from_every_module(): void
    {
        $client = Client::factory()->create(['status' => 'lead']);
        $offer = Offer::factory()->create(['client_id' => $client->id, 'status' => 'draft']);
        Installation::factory()->create(['client_id' => $client->id, 'status' => 'scheduled']);
        Ticket::factory()->create(['client_id' => $client->id, 'status' => 'open']);

        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('recentLeads', 1)
            ->has('pendingOffers', 1)
            ->has('activeInstallations', 1)
            ->has('openTickets', 1)
            ->where('pendingOffers.0.id', $offer->id)
        );
    }

    public function test_dashboard_exposes_professional_business_metrics(): void
    {
        $client = Client::factory()->create();
        Offer::factory()->create(['client_id' => $client->id, 'status' => 'accepted', 'total_amount' => 2400]);
        Installation::factory()->create(['client_id' => $client->id, 'status' => 'scheduled']);
        Installation::factory()->create(['client_id' => $client->id, 'status' => 'completed']);
        Invoice::factory()->create(['status' => 'overdue', 'amount' => 600]);

        $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.acceptedValue', 2400)
                ->where('stats.installationsActive', 1)
                ->where('stats.installationsCompleted', 1)
                ->where('stats.invoicesOverdue', 1)
                ->where('stats.overdueAmount', 600)
            );
    }

    public function test_dashboard_and_reports_use_remaining_invoice_balance(): void
    {
        Invoice::factory()->create(['status' => 'unpaid', 'amount' => 1000, 'paid_amount' => 300, 'due_at' => now()->addDays(15)]);
        Invoice::factory()->create(['status' => 'overdue', 'amount' => 800, 'paid_amount' => 200, 'due_at' => now()->subDays(5)]);

        $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.unpaidAmount', 1300)
                ->where('stats.overdueAmount', 600)
            );

        $this->actingAs($this->adminUser)
            ->get(route('admin.reports'))
            ->assertInertia(fn ($page) => $page
                ->where('financial.unpaidTotal', 1300)
                ->where('financial.overdueCount', 1)
            );
    }

    public function test_admin_can_accept_a_pending_offer_directly_from_the_dashboard(): void
    {
        $client = Client::factory()->create();
        $offer = Offer::factory()->create(['client_id' => $client->id, 'status' => 'sent']);

        $this->actingAs($this->adminUser)
            ->patch(route('sales.offers.status', $offer), ['status' => 'accepted'])
            ->assertRedirect();

        $this->assertDatabaseHas('offers', ['id' => $offer->id, 'status' => 'accepted']);
        $this->assertDatabaseHas('installations', ['offer_id' => $offer->id]);
    }

    public function test_admin_can_resolve_a_ticket_directly_from_the_dashboard(): void
    {
        $ticket = Ticket::factory()->create(['status' => 'open']);

        $this->actingAs($this->adminUser)
            ->patch(route('technical.tickets.status', $ticket), ['status' => 'resolved'])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'resolved']);
    }

    public function test_authenticated_user_can_mark_all_notifications_as_read(): void
    {
        $this->adminUser->notifications()->create([
            'id' => '00000000-0000-0000-0000-000000000001',
            'type' => 'test',
            'data' => ['title' => 'Test'],
        ]);

        $this->actingAs($this->adminUser)
            ->patch(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertNotNull($this->adminUser->notifications()->find('00000000-0000-0000-0000-000000000001')->read_at);
    }

    public function test_profit_report_aggregates_completed_installations_by_client_and_technician(): void
    {
        $client = Client::factory()->create(['name' => 'Client Profit']);
        $technician = User::factory()->create(['name' => 'Tehnician Profit']);
        $offer = Offer::factory()->create(['client_id' => $client->id, 'status' => 'accepted', 'total_amount' => 2000]);

        Installation::factory()->create([
            'client_id' => $client->id,
            'offer_id' => $offer->id,
            'technician_id' => $technician->id,
            'status' => 'completed',
            'completed_at' => now(),
            'material_items' => [],
            'service_items' => [],
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.reports.profit'));

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Reports/Profit')
            ->where('summary.installations', 1)
            ->where('summary.revenue', 2000)
            ->where('summary.profit', 2000)
            ->where('byClient.0.client', 'Client Profit')
            ->where('byTechnician.0.technician', 'Tehnician Profit')
        );
    }

    public function test_profit_report_excludes_installations_outside_selected_period(): void
    {
        Installation::factory()->create([
            'status' => 'completed',
            'completed_at' => now()->subYear(),
            'material_items' => [],
            'service_items' => [],
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.reports.profit', [
            'from' => now()->subMonth()->format('Y-m-d'),
            'to' => now()->format('Y-m-d'),
        ]));

        $response->assertInertia(fn ($page) => $page
            ->where('summary.installations', 0)
        );
    }
}
