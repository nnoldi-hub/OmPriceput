<?php

namespace Tests\Feature\Sales;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientCrudTest extends TestCase
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

    public function test_guest_cannot_access_sales_module(): void
    {
        $this->get(route('sales.clients.index'))->assertRedirect(route('login'));
    }

    public function test_technician_sees_clients_but_cannot_manage_them(): void
    {
        $tech = User::factory()->create();
        $tech->assignRole('tehnic');

        $this->assertTrue($tech->hasPermissionTo('clients.view'));
        $this->assertFalse($tech->hasPermissionTo('clients.manage'));

        $this->actingAs($tech)->get(route('sales.clients.index'))->assertOk();
        $this->actingAs($tech)->get(route('sales.clients.create'))->assertForbidden();
        $this->actingAs($tech)->get(route('sales.clients.edit', Client::factory()->create()))->assertForbidden();
    }

    public function test_client_portal_user_cannot_access_sales_module(): void
    {
        $portalUser = User::factory()->create();
        $portalUser->assignRole('client');

        $this->actingAs($portalUser)->get(route('sales.clients.index'))->assertForbidden();
    }

    public function test_sales_user_cannot_access_technical_module(): void
    {
        $this->actingAs($this->salesUser)->get(route('technical.equipment.index'))->assertForbidden();
        $this->actingAs($this->salesUser)->get(route('technical.installations.index'))->assertForbidden();
        $this->actingAs($this->salesUser)->get(route('technical.tickets.index'))->assertForbidden();
    }

    public function test_sales_user_can_list_clients(): void
    {
        Client::factory()->count(3)->create();

        $this->actingAs($this->salesUser)
            ->get(route('sales.clients.index'))
            ->assertOk();
    }

    public function test_sales_user_can_create_a_client(): void
    {
        $response = $this->actingAs($this->salesUser)->post(route('sales.clients.store'), [
            'name' => 'Andrei Popescu',
            'phone' => '0722000000',
            'email' => 'andrei@example.com',
            'source' => 'manual',
            'status' => 'lead',
            'pipeline_stage' => 'new',
        ]);

        $client = Client::firstWhere('email', 'andrei@example.com');
        $response->assertRedirect(route('sales.clients.show', $client));

        $this->assertDatabaseHas('clients', [
            'name' => 'Andrei Popescu',
            'phone' => '0722000000',
        ]);
    }

    public function test_client_creation_requires_name(): void
    {
        $this->actingAs($this->salesUser)
            ->post(route('sales.clients.store'), ['source' => 'manual', 'status' => 'lead'])
            ->assertSessionHasErrors('name');
    }

    public function test_sales_user_can_update_a_client(): void
    {
        $client = Client::factory()->create(['status' => 'lead']);

        $this->actingAs($this->salesUser)->put(route('sales.clients.update', $client), [
            'name' => $client->name,
            'source' => $client->source,
            'status' => 'client',
            'pipeline_stage' => 'won',
        ])->assertRedirect(route('sales.clients.show', $client));

        $this->assertDatabaseHas('clients', ['id' => $client->id, 'status' => 'client']);
    }

    public function test_sales_user_can_delete_a_client(): void
    {
        $client = Client::factory()->create();

        $this->actingAs($this->salesUser)
            ->delete(route('sales.clients.destroy', $client))
            ->assertRedirect(route('sales.clients.index'));

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_client_list_can_be_searched_by_name(): void
    {
        Client::factory()->create(['name' => 'Maria Ionescu']);
        Client::factory()->create(['name' => 'George Vasile']);

        $response = $this->actingAs($this->salesUser)
            ->get(route('sales.clients.index', ['search' => 'Maria']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Sales/Clients/Index')
            ->has('clients.data', 1)
        );
    }

    public function test_clients_export_downloads_a_file(): void
    {
        Client::factory()->count(2)->create();

        $this->actingAs($this->salesUser)
            ->get(route('sales.clients.export'))
            ->assertOk();
    }

    public function test_client_page_includes_invoice_financial_statement(): void
    {
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create(['client_id' => $client->id, 'amount' => 1000, 'paid_amount' => 300, 'status' => 'unpaid']);
        InvoicePayment::create(['invoice_id' => $invoice->id, 'amount' => 300, 'payment_method' => 'transfer', 'paid_at' => now()]);

        $this->actingAs($this->salesUser)
            ->get(route('sales.clients.show', $client))
            ->assertInertia(fn ($page) => $page
                ->component('Sales/Clients/Show')
                ->where('summary.invoiceTotal', 1000)
                ->where('summary.invoicePaid', 300)
                ->where('summary.invoiceBalance', 700)
                ->where('summary.overdueInvoices', 0)
                ->where('client.invoices.0.payments.0.amount', '300.00')
            );
    }

    public function test_client_financial_statement_pdf_downloads(): void
    {
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create(['client_id' => $client->id, 'amount' => 1000, 'paid_amount' => 300, 'status' => 'unpaid']);
        InvoicePayment::create(['invoice_id' => $invoice->id, 'amount' => 300, 'payment_method' => 'transfer', 'paid_at' => now()]);

        $this->actingAs($this->salesUser)
            ->get(route('sales.clients.statement.pdf', $client))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_client_financial_statement_excel_downloads(): void
    {
        $client = Client::factory()->create();
        Invoice::factory()->create(['client_id' => $client->id, 'amount' => 1000, 'paid_amount' => 300, 'status' => 'unpaid']);

        $this->actingAs($this->salesUser)
            ->get(route('sales.clients.statement.excel', $client))
            ->assertOk();
    }
}
