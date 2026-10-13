<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Offer;
use App\Models\User;
use App\Services\SmsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_driver_records_sms_as_sent_without_calling_any_http_endpoint(): void
    {
        Http::fake();

        $log = (new SmsService)->send('0722000000', 'Test message');

        $this->assertEquals('sent', $log->status);
        $this->assertEquals('log', $log->driver);
        Http::assertNothingSent();
    }

    public function test_http_driver_records_failure_on_non_successful_response(): void
    {
        config(['services.sms.driver' => 'http', 'services.sms.api_url' => 'https://sms.example.test/send']);
        Http::fake(['sms.example.test/*' => Http::response('bad request', 400)]);

        $log = (new SmsService)->send('0722000000', 'Test message');

        $this->assertEquals('failed', $log->status);
        $this->assertNotNull($log->error);
    }

    public function test_http_driver_records_success_on_successful_response(): void
    {
        config(['services.sms.driver' => 'http', 'services.sms.api_url' => 'https://sms.example.test/send']);
        Http::fake(['sms.example.test/*' => Http::response(['status' => 'queued'], 200)]);

        $log = (new SmsService)->send('0722000000', 'Test message');

        $this->assertEquals('sent', $log->status);
        $this->assertNull($log->error);
    }

    public function test_lead_submission_sends_sms_to_staff_with_phone_numbers(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['phone' => '0733111222']);
        $admin->assignRole('admin');
        $adminWithoutPhone = User::factory()->create(['phone' => null]);
        $adminWithoutPhone->assignRole('vanzari');

        $this->post(route('public.lead.store'), [
            'name' => 'Test Lead',
            'phone' => '0722999888',
            'privacy_consent' => '1',
        ]);

        $this->assertDatabaseHas('sms_logs', ['phone' => '0733111222']);
        $this->assertDatabaseHas('sms_logs', ['phone' => '0722999888']);
        $this->assertDatabaseCount('sms_logs', 2);
    }

    public function test_marking_offer_as_sent_sends_sms_to_client_with_a_phone(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $sales = User::factory()->create();
        $sales->assignRole('vanzari');

        $client = Client::factory()->create(['phone' => '0733555666', 'email' => null]);
        $offer = Offer::factory()->create(['client_id' => $client->id, 'user_id' => $sales->id, 'status' => 'draft']);

        $this->actingAs($sales)->patch(route('sales.offers.status', $offer), ['status' => 'sent']);

        $this->assertDatabaseHas('sms_logs', ['phone' => '0733555666']);
    }
}
