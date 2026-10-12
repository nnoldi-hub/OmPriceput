<?php

namespace Tests\Unit;

use App\Models\Measurement;
use App\Models\MeasurementObject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementMathTest extends TestCase
{
    use RefreshDatabase;

    public function test_object_area_is_length_times_width_times_quantity(): void
    {
        $object = MeasurementObject::factory()->create([
            'length' => 4.5,
            'width' => 3.2,
            'quantity' => 1,
        ]);

        $this->assertSame(14.4, $object->area());
    }

    public function test_object_area_scales_with_quantity(): void
    {
        $object = MeasurementObject::factory()->create([
            'length' => 2,
            'width' => 2,
            'quantity' => 3,
        ]);

        $this->assertSame(12.0, $object->area());
    }

    public function test_object_area_is_zero_when_a_dimension_is_missing(): void
    {
        $object = MeasurementObject::factory()->create([
            'length' => 4.5,
            'width' => null,
            'quantity' => 1,
        ]);

        $this->assertSame(0.0, $object->area());
    }

    public function test_object_perimeter_is_two_times_width_plus_height_scaled_by_quantity(): void
    {
        $object = MeasurementObject::factory()->create([
            'length' => 4.5,
            'width' => 3.2,
            'quantity' => 2,
        ]);

        $this->assertSame(30.8, $object->perimeter());
    }

    public function test_measurement_total_area_sums_objects(): void
    {
        $measurement = Measurement::factory()->create();
        MeasurementObject::factory()->create(['measurement_id' => $measurement->id, 'length' => 4.5, 'width' => 3.2, 'quantity' => 1]);
        MeasurementObject::factory()->create(['measurement_id' => $measurement->id, 'length' => 2, 'width' => 2, 'quantity' => 2]);

        $this->assertSame(22.4, $measurement->totalArea());
    }

    public function test_measurement_total_perimeter_sums_objects(): void
    {
        $measurement = Measurement::factory()->create();
        MeasurementObject::factory()->create(['measurement_id' => $measurement->id, 'length' => 4, 'width' => 3, 'quantity' => 1]);
        MeasurementObject::factory()->create(['measurement_id' => $measurement->id, 'length' => 10, 'width' => 10, 'quantity' => 1]);

        $this->assertSame(54.0, $measurement->totalPerimeter());
    }

    public function test_empty_measurement_has_zero_totals(): void
    {
        $measurement = Measurement::factory()->create();

        $this->assertSame(0.0, $measurement->totalArea());
        $this->assertSame(0.0, $measurement->totalPerimeter());
    }
}
