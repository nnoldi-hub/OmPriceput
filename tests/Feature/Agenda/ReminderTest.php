<?php

namespace Tests\Feature\Agenda;

use App\Models\AgendaItem;
use App\Models\User;
use App\Notifications\AgendaReminder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReminderTest extends TestCase
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

    public function test_command_sends_reminders_for_due_unnotified_items(): void
    {
        Notification::fake();

        $item = AgendaItem::factory()->create([
            'title' => 'Sun-i clientul pentru confirmare',
            'reminder_at' => now()->subHour(),
        ]);

        $this->artisan('agenda:send-reminders')->assertSuccessful();

        Notification::assertSentTo($this->admin, AgendaReminder::class);
        $this->assertNotNull($item->fresh()->reminded_at);
    }

    public function test_command_does_not_send_twice_for_the_same_item(): void
    {
        Notification::fake();

        AgendaItem::factory()->create([
            'reminder_at' => now()->subHour(),
            'reminded_at' => now()->subMinutes(30),
        ]);

        $this->artisan('agenda:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_command_skips_completed_and_archived_items(): void
    {
        Notification::fake();

        AgendaItem::factory()->create(['reminder_at' => now()->subHour(), 'status' => 'gata']);
        AgendaItem::factory()->create(['reminder_at' => now()->subHour(), 'status' => 'arhivat']);

        $this->artisan('agenda:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_command_skips_future_reminders(): void
    {
        Notification::fake();

        AgendaItem::factory()->create(['reminder_at' => now()->addDay()]);

        $this->artisan('agenda:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_changing_reminder_time_allows_recalling(): void
    {
        $item = AgendaItem::factory()->create(['reminder_at' => now()->subHour(), 'reminded_at' => now()]);

        $this->actingAs($this->admin)->patch(route('agenda.items.update', $item), [
            'reminder_at' => now()->addHour()->format('Y-m-d H:i'),
        ])->assertRedirect();

        $this->assertNull($item->fresh()->reminded_at);
    }
}
