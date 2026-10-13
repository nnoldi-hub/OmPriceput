<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Installation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingActionTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'abcdefghijklmnopqrstuvwx';

    private function booking(array $attributes = []): Installation
    {
        $client = Client::factory()->create(['phone' => '0733111222']);

        return Installation::factory()->create(array_merge([
            'client_id' => $client->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(2)->setTime(10, 0),
            'action_token' => self::TOKEN,
        ], $attributes));
    }

    public function test_action_page_shows_the_details_and_is_not_indexed(): void
    {
        $booking = $this->booking(['address' => 'Str. Test 1']);

        $response = $this->get(route('booking.action', self::TOKEN));

        $response->assertOk();
        $response->assertSee('Programare #'.$booking->id);
        $response->assertSee('Str. Test 1');
        $response->assertSee('Confirmă programarea');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_link_is_not_tracked_and_is_disallowed_in_robots(): void
    {
        $this->get(route('robots'))->assertOk()->assertSee('Disallow: /c/');
    }

    public function test_unknown_or_malformed_token_returns_404(): void
    {
        $this->get('/c/'.str_repeat('a', 24))->assertNotFound();
        $this->get('/c/short')->assertNotFound();
    }

    public function test_confirm_marks_the_booking_and_notifies_the_client(): void
    {
        $booking = $this->booking();

        $this->post(route('booking.action.update', self::TOKEN), ['action' => 'confirm'])
            ->assertRedirect(route('booking.action', self::TOKEN));

        $this->assertNotNull($booking->refresh()->confirmed_at);
        $this->assertDatabaseHas('sms_logs', ['phone' => '0733111222']);
    }

    public function test_confirming_twice_sends_only_one_sms(): void
    {
        $this->booking();

        $this->post(route('booking.action.update', self::TOKEN), ['action' => 'confirm']);
        $this->post(route('booking.action.update', self::TOKEN), ['action' => 'confirm']);

        $this->assertDatabaseCount('sms_logs', 1);
    }

    public function test_decline_cancels_the_booking_and_frees_the_slot(): void
    {
        $booking = $this->booking();

        $this->post(route('booking.action.update', self::TOKEN), ['action' => 'cancel'])
            ->assertRedirect(route('booking.action', self::TOKEN));

        $this->assertSame('cancelled', $booking->refresh()->status);
        $this->assertDatabaseHas('sms_logs', ['phone' => '0733111222']);
    }

    public function test_a_finished_booking_can_no_longer_be_changed(): void
    {
        $booking = $this->booking(['status' => 'completed']);

        $this->post(route('booking.action.update', self::TOKEN), ['action' => 'confirm']);

        $this->assertNull($booking->refresh()->confirmed_at);
        $this->assertSame('completed', $booking->status);
        $this->assertDatabaseCount('sms_logs', 0);
    }

    public function test_a_past_booking_cannot_be_confirmed(): void
    {
        $booking = $this->booking(['scheduled_at' => now()->subDay()]);

        $this->post(route('booking.action.update', self::TOKEN), ['action' => 'confirm']);

        $this->assertNull($booking->refresh()->confirmed_at);
        $this->assertDatabaseCount('sms_logs', 0);
    }

    public function test_the_action_token_is_hidden_from_serialization(): void
    {
        $booking = $this->booking();

        $this->assertArrayNotHasKey('action_token', $booking->toArray());
    }
}
