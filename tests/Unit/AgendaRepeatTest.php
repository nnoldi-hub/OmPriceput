<?php

namespace Tests\Unit;

use App\Models\AgendaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaRepeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_named_intervals_map_to_days(): void
    {
        $this->assertSame(1, AgendaItem::repeatIntervalDays('daily'));
        $this->assertSame(7, AgendaItem::repeatIntervalDays('weekly'));
        $this->assertSame(30, AgendaItem::repeatIntervalDays('monthly'));
        $this->assertSame(365, AgendaItem::repeatIntervalDays('yearly'));
    }

    public function test_number_patterns_map_to_days(): void
    {
        $this->assertSame(3, AgendaItem::repeatIntervalDays('3d'));
        $this->assertSame(14, AgendaItem::repeatIntervalDays('2w'));
        $this->assertSame(60, AgendaItem::repeatIntervalDays('2m'));
        $this->assertSame(730, AgendaItem::repeatIntervalDays('2y'));
        $this->assertSame(45, AgendaItem::repeatIntervalDays('45d'));
    }

    public function test_intervals_are_case_insensitive_and_trimmed(): void
    {
        $this->assertSame(1, AgendaItem::repeatIntervalDays(' Daily '));
        $this->assertSame(3, AgendaItem::repeatIntervalDays('3D'));
        $this->assertSame(1, AgendaItem::repeatIntervalDays('d'));
    }

    public function test_invalid_and_blank_repeats_return_null(): void
    {
        $this->assertNull(AgendaItem::repeatIntervalDays(null));
        $this->assertNull(AgendaItem::repeatIntervalDays(''));
        $this->assertNull(AgendaItem::repeatIntervalDays('   '));
        $this->assertNull(AgendaItem::repeatIntervalDays('weekly-ish'));
        $this->assertNull(AgendaItem::repeatIntervalDays('x3'));
    }

    public function test_advance_repeat_moves_due_date_and_keeps_item_open(): void
    {
        $due = now()->startOfDay()->addDays(2);
        $item = AgendaItem::factory()->create([
            'status' => 'in_lucru',
            'due_at' => $due,
            'repeat' => '3d',
        ]);

        $item->advanceRepeat();
        $fresh = $item->fresh();

        $this->assertSame('de_facut', $fresh->status);
        $this->assertNotNull($fresh->completed_at);
        $this->assertEqualsWithDelta($due->addDays(3)->timestamp, $fresh->due_at->timestamp, 5);
    }

    public function test_advance_repeat_moves_reminder_only_when_present(): void
    {
        $due = now()->startOfDay()->addDays(2);
        $reminder = now()->startOfDay()->addDays(1);
        $item = AgendaItem::factory()->create([
            'due_at' => $due,
            'reminder_at' => $reminder,
            'repeat' => '3d',
        ]);

        $item->advanceRepeat();
        $fresh = $item->fresh();

        $this->assertEqualsWithDelta($reminder->addDays(3)->timestamp, $fresh->reminder_at->timestamp, 5);
    }

    public function test_advance_repeat_without_reminder_leaves_it_null(): void
    {
        $item = AgendaItem::factory()->create([
            'due_at' => now(),
            'reminder_at' => null,
            'repeat' => 'weekly',
        ]);

        $item->advanceRepeat();

        $this->assertNull($item->fresh()->reminder_at);
    }

    public function test_advance_repeat_without_due_date_uses_today_as_base(): void
    {
        $item = AgendaItem::factory()->create([
            'due_at' => null,
            'repeat' => 'weekly',
        ]);

        $item->advanceRepeat();

        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, $item->fresh()->due_at->timestamp, 5);
    }

    public function test_advance_stores_last_completion_time(): void
    {
        $item = AgendaItem::factory()->create(['repeat' => 'daily', 'due_at' => now()]);

        $item->advanceRepeat();

        $this->assertNotNull($item->fresh()->completed_at);
    }

    public function test_non_repeating_item_advance_marks_it_gata(): void
    {
        $due = now()->startOfDay()->addDays(2);
        $item = AgendaItem::factory()->create([
            'status' => 'de_facut',
            'due_at' => $due,
            'repeat' => null,
        ]);

        $item->advanceRepeat();
        $fresh = $item->fresh();

        $this->assertSame('gata', $fresh->status);
        $this->assertNotNull($fresh->completed_at);
        $this->assertEqualsWithDelta($due->timestamp, $fresh->due_at->timestamp, 5);
    }
}
