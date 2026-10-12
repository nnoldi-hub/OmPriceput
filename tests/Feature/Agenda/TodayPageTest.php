<?php

namespace Tests\Feature\Agenda;

use App\Models\AgendaItem;
use App\Models\Client;
use App\Models\Installation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodayPageTest extends TestCase
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

    public function test_today_page_is_the_agenda_home(): void
    {
        $this->actingAs($this->admin)
            ->get(route('agenda.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Agenda/Today'));
    }

    public function test_today_page_groups_overdue_due_today_reminders_and_appointments(): void
    {
        $overdue = AgendaItem::factory()->create([
            'status' => 'de_facut',
            'due_at' => now()->subDays(2),
            'title' => 'Sarcina intarziata',
        ]);

        $dueToday = AgendaItem::factory()->create([
            'status' => 'de_facut',
            'due_at' => now()->addHours(2),
            'title' => 'Sarcina de azi',
        ]);

        $reminder = AgendaItem::factory()->create([
            'status' => 'de_facut',
            'reminder_at' => now()->subHour(),
            'title' => 'Memento venit',
        ]);

        AgendaItem::factory()->create([
            'status' => 'de_facut',
            'due_at' => now()->addWeek(),
            'title' => 'Sarcina viitoare',
        ]);

        AgendaItem::factory()->create([
            'status' => 'gata',
            'due_at' => now()->subDay(),
            'title' => 'Deja gata',
        ]);

        $client = Client::factory()->create();
        Installation::factory()->create([
            'client_id' => $client->id,
            'scheduled_at' => now()->addHours(3),
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->admin)
            ->get(route('agenda.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('overdue', 1)
                ->has('dueToday', 1)
                ->has('reminders', 1)
                ->has('appointments', 1)
                ->where('overdue.0.title', 'Sarcina intarziata')
                ->where('dueToday.0.title', 'Sarcina de azi')
                ->where('reminders.0.title', 'Memento venit')
            );
    }

    public function test_cancelled_installations_are_not_today_appointments(): void
    {
        Installation::factory()->create([
            'scheduled_at' => now()->addHours(3),
            'status' => 'cancelled',
        ]);

        $this->actingAs($this->admin)
            ->get(route('agenda.index'))
            ->assertInertia(fn ($page) => $page->has('appointments', 0));
    }

    public function test_checking_a_non_repeating_item_marks_it_gata(): void
    {
        $item = AgendaItem::factory()->create(['status' => 'de_facut', 'due_at' => now()->subDay()]);

        $this->actingAs($this->admin)
            ->patch(route('agenda.items.toggle', $item), [])
            ->assertRedirect();

        $fresh = $item->fresh();
        $this->assertSame('gata', $fresh->status);
        $this->assertNotNull($fresh->completed_at);
    }

    public function test_checking_a_repeating_item_reschedules_it_and_keeps_it_open(): void
    {
        $due = now()->subDays(2);
        $item = AgendaItem::factory()->create([
            'status' => 'de_facut',
            'due_at' => $due,
            'repeat' => '3d',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('agenda.items.toggle', $item), [])
            ->assertRedirect();

        $fresh = $item->fresh();
        $this->assertSame('de_facut', $fresh->status);
        $this->assertNotNull($fresh->completed_at);
        $this->assertEqualsWithDelta($due->addDays(3)->timestamp, $fresh->due_at->timestamp, 5);
    }

    public function test_daily_repeat_advances_to_tomorrow(): void
    {
        $item = AgendaItem::factory()->create([
            'status' => 'de_facut',
            'due_at' => now()->startOfDay(),
            'repeat' => 'daily',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('agenda.items.toggle', $item), [])
            ->assertRedirect();

        $this->assertEqualsWithDelta(
            now()->startOfDay()->addDay()->timestamp,
            $item->fresh()->due_at->timestamp,
            5
        );
    }

    public function test_reopening_a_completed_item(): void
    {
        $item = AgendaItem::factory()->create(['status' => 'gata', 'completed_at' => now()]);

        $this->actingAs($this->admin)
            ->patch(route('agenda.items.toggle', $item), [])
            ->assertRedirect();

        $fresh = $item->fresh();
        $this->assertSame('de_facut', $fresh->status);
        $this->assertNull($fresh->completed_at);
    }

    public function test_repeat_can_be_set_when_sorting_from_inbox(): void
    {
        $item = AgendaItem::factory()->create(['status' => 'inbox']);

        $this->actingAs($this->admin)->patch(route('agenda.items.update', $item), [
            'title' => $item->title,
            'text' => $item->text,
            'type' => 'task',
            'status' => 'de_facut',
            'priority' => 'normal',
            'project_id' => null,
            'due_at' => null,
            'repeat' => '3d',
        ])->assertRedirect();

        $this->assertSame('3d', $item->fresh()->repeat);
    }
}
