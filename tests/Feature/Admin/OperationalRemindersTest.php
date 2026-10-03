<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\Installation;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\OperationalReminder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OperationalRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sends_reminders_for_overdue_invoices_installations_and_low_stock(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $technician = User::factory()->create();
        $technician->assignRole('tehnic');
        $client = Client::factory()->create();

        Invoice::factory()->create(['status' => 'overdue', 'amount' => 1000, 'paid_amount' => 200]);
        Installation::factory()->create([
            'client_id' => $client->id,
            'technician_id' => $technician->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay()->setTime(10, 0),
        ]);
        Equipment::factory()->create(['stock_quantity' => 1, 'minimum_stock' => 5]);

        Artisan::call('operations:send-reminders');

        $this->assertSame(1, $admin->notifications()->where('type', 'App\\Notifications\\OperationalReminder')->whereJsonContains('data->kind', 'overdue_invoices')->count());
        $this->assertSame(1, $technician->notifications()->whereJsonContains('data->kind', 'tomorrow_installation')->count());
        $this->assertSame(1, $admin->notifications()->whereJsonContains('data->kind', 'low_stock')->count());
    }

    public function test_operational_reminder_only_uses_database_channel_by_default(): void
    {
        $user = User::factory()->create();
        $notification = new OperationalReminder('overdue_invoices', 'Test', 'Mesaj test');

        $this->assertSame(['database'], $notification->via($user));
    }

    public function test_operational_reminder_adds_mail_channel_when_enabled_in_settings(): void
    {
        Setting::set('operational_reminders_email_enabled', '1');

        $user = User::factory()->create();
        $notification = new OperationalReminder('overdue_invoices', 'Test', 'Mesaj test');

        $this->assertSame(['database', 'mail'], $notification->via($user));
    }

    public function test_admin_can_enable_email_notifications_from_settings(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'company_name' => 'Omul Potrivit',
            'company_email' => 'contact@omulpotrivit.ro',
            'company_phone' => '0722000000',
            'company_hours' => 'Luni - Vineri',
            'invoice_series' => 'OP',
            'vat_percentage' => 19,
            'minimum_profit_margin' => 20,
            'operational_reminders_email_enabled' => true,
        ])->assertRedirect();

        $this->assertSame('1', Setting::get('operational_reminders_email_enabled'));
    }

    public function test_reminder_command_sends_mail_when_email_notifications_are_enabled(): void
    {
        Notification::fake();
        Setting::set('operational_reminders_email_enabled', '1');

        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Invoice::factory()->create(['status' => 'overdue', 'amount' => 1000, 'paid_amount' => 200]);

        Artisan::call('operations:send-reminders');

        Notification::assertSentTo($admin, OperationalReminder::class, function ($notification) use ($admin) {
            return in_array('mail', $notification->via($admin), true);
        });
    }
}
