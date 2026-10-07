<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\DayOff;
use App\Models\Installation;
use App\Models\WorkingHour;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $service;

    // Luni 2026-10-12, "acum" = vineri 2026-10-09 la 10:00
    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AvailabilityService();
        $this->now = CarbonImmutable::parse('2026-10-09 10:00');

        config(['booking.min_notice_hours' => 12]);

        foreach (range(1, 7) as $day) {
            [$s, $e] = $day <= 5 ? ['17:30', '20:00'] : ['09:00', '18:00'];
            WorkingHour::create(['day_of_week' => $day, 'start_time' => $s, 'end_time' => $e, 'is_active' => true]);
        }
    }

    private function book(string $datetime, float $hours): void
    {
        $client = Client::query()->first() ?? Client::forceCreate(['name' => 'Test']);

        Installation::forceCreate([
            'client_id' => $client->id,
            'scheduled_at' => $datetime,
            'labor_hours' => $hours,
            'status' => 'scheduled',
            'type' => 'instalare',
        ]);
    }

    private function times($slots): array
    {
        return $slots->map(fn ($s) => $s->format('H:i'))->all();
    }

    public function test_free_weekday_offers_evening_slots_only(): void
    {
        $slots = $this->service->slotsForDate(CarbonImmutable::parse('2026-10-12'), 60, $this->now);

        $this->assertSame('17:30', $this->times($slots)[0]);
        $this->assertSame('19:00', last($this->times($slots)));
    }

    public function test_weekend_offers_full_day(): void
    {
        $slots = $this->service->slotsForDate(CarbonImmutable::parse('2026-10-10'), 60, $this->now);

        $this->assertSame('09:00', $this->times($slots)[0]);
        $this->assertSame('17:00', last($this->times($slots)));
    }

    public function test_booking_blocks_overlapping_slots_with_travel_buffer(): void
    {
        // Programare 18:00-19:00 (1h) => ocupat 17:30-19:30 cu marja
        $this->book('2026-10-12 18:00:00', 1);

        $slots = $this->times($this->service->slotsForDate(CarbonImmutable::parse('2026-10-12'), 30, $this->now));

        $this->assertNotContains('17:30', $slots);
        $this->assertNotContains('18:30', $slots);
        $this->assertContains('19:30', $slots);
    }

    public function test_duration_that_does_not_fit_before_end_of_day_is_excluded(): void
    {
        $slots = $this->times($this->service->slotsForDate(CarbonImmutable::parse('2026-10-12'), 120, $this->now));

        $this->assertSame(['17:30', '18:00'], $slots);
    }

    public function test_past_dates_have_no_slots(): void
    {
        $slots = $this->service->slotsForDate(CarbonImmutable::parse('2026-10-05'), 60, $this->now);

        $this->assertTrue($slots->isEmpty());
    }

    public function test_minimum_notice_removes_too_soon_slots(): void
    {
        // acum = sambata 10:00, notice 12h => cel mai devreme 22:00 (nimic azi)
        $now = CarbonImmutable::parse('2026-10-10 10:00');

        $slots = $this->service->slotsForDate(CarbonImmutable::parse('2026-10-10'), 60, $now);

        $this->assertTrue($slots->isEmpty());
    }

    public function test_day_off_has_no_slots(): void
    {
        DayOff::create(['date' => '2026-10-10', 'reason' => 'liber']);

        $slots = $this->service->slotsForDate(CarbonImmutable::parse('2026-10-10'), 60, $this->now);

        $this->assertTrue($slots->isEmpty());
    }

    public function test_cancelled_booking_does_not_block(): void
    {
        $this->book('2026-10-12 18:00:00', 1);
        Installation::query()->update(['status' => 'cancelled']);

        $slots = $this->times($this->service->slotsForDate(CarbonImmutable::parse('2026-10-12'), 60, $this->now));

        $this->assertContains('17:30', $slots);
    }

    public function test_is_slot_free_matches_slot_list(): void
    {
        $this->book('2026-10-12 18:00:00', 1);

        $this->assertFalse($this->service->isSlotFree(CarbonImmutable::parse('2026-10-12 18:00'), 60, $this->now));
        $this->assertTrue($this->service->isSlotFree(CarbonImmutable::parse('2026-10-12 19:30'), 30, $this->now));
    }
}