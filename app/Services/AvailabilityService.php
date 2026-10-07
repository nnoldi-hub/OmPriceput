<?php

namespace App\Services;

use App\Models\DayOff;
use App\Models\Installation;
use App\Models\WorkingHour;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Sloturile libere dintr-o zi, pentru o durata data (in minute).
     *
     * @return Collection<int, CarbonImmutable> momentele de start disponibile
     */
    public function slotsForDate(CarbonImmutable $date, int $durationMinutes, ?CarbonImmutable $now = null): Collection
    {
        $now ??= CarbonImmutable::now();
        $date = $date->startOfDay();

        if (! $this->isDateBookable($date, $now)) {
            return collect();
        }

        $hours = WorkingHour::where('day_of_week', $date->dayOfWeekIso)
            ->where('is_active', true)
            ->first();

        if (! $hours || ! $hours->start_time || ! $hours->end_time) {
            return collect();
        }

        $dayStart = $this->atTime($date, $hours->start_time);
        $dayEnd = $this->atTime($date, $hours->end_time);

        $step = (int) config('booking.slot_step_minutes', 30);
        $buffer = (int) config('booking.buffer_minutes', 30);
        $earliest = $now->addHours((int) config('booking.min_notice_hours', 12));

        $busy = $this->busyIntervals($date, $buffer);

        $slots = collect();
        for ($start = $dayStart; $start->addMinutes($durationMinutes)->lte($dayEnd); $start = $start->addMinutes($step)) {
            $end = $start->addMinutes($durationMinutes);

            if ($start->lt($earliest)) {
                continue;
            }

            if ($this->overlapsAny($start, $end, $busy)) {
                continue;
            }

            $slots->push($start);
        }

        return $slots;
    }

    /**
     * Verificare finala inainte de salvare (folosita si in tranzactie).
     */
    public function isSlotFree(CarbonImmutable $start, int $durationMinutes, ?CarbonImmutable $now = null): bool
    {
        return $this->slotsForDate($start, $durationMinutes, $now)
            ->contains(fn (CarbonImmutable $slot) => $slot->equalTo($start));
    }

    /**
     * Zilele (Y-m-d) care au cel putin un slot liber, pentru calendar.
     *
     * @return Collection<int, string>
     */
    public function availableDates(int $durationMinutes, ?CarbonImmutable $now = null): Collection
    {
        $now ??= CarbonImmutable::now();
        $days = (int) config('booking.max_days_ahead', 30);

        return collect(range(0, $days))
            ->map(fn (int $i) => $now->startOfDay()->addDays($i))
            ->filter(fn (CarbonImmutable $d) => $this->slotsForDate($d, $durationMinutes, $now)->isNotEmpty())
            ->map(fn (CarbonImmutable $d) => $d->toDateString())
            ->values();
    }

    private function isDateBookable(CarbonImmutable $date, CarbonImmutable $now): bool
    {
        if ($date->lt($now->startOfDay())) {
            return false;
        }

        if ($date->gt($now->startOfDay()->addDays((int) config('booking.max_days_ahead', 30)))) {
            return false;
        }

        return ! DayOff::whereDate('date', $date->toDateString())->exists();
    }

    /**
     * Intervalele ocupate de programari, extinse cu marja de deplasare.
     *
     * @return array<int, array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    private function busyIntervals(CarbonImmutable $date, int $buffer): array
    {
        return Installation::query()
            ->whereIn('status', config('booking.blocking_statuses', ['scheduled', 'in_progress']))
            ->whereBetween('scheduled_at', [$date->startOfDay(), $date->endOfDay()])
            ->get(['scheduled_at', 'labor_hours'])
            ->map(function (Installation $i) use ($buffer) {
                $start = CarbonImmutable::instance($i->scheduled_at);
                $minutes = (int) round(((float) $i->labor_hours ?: 1) * 60);

                return [$start->subMinutes($buffer), $start->addMinutes($minutes + $buffer)];
            })
            ->all();
    }

    private function overlapsAny(CarbonImmutable $start, CarbonImmutable $end, array $busy): bool
    {
        foreach ($busy as [$busyStart, $busyEnd]) {
            if ($start->lt($busyEnd) && $end->gt($busyStart)) {
                return true;
            }
        }

        return false;
    }

    private function atTime(CarbonImmutable $date, string $time): CarbonImmutable
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $date->setTime($h, $m);
    }
}