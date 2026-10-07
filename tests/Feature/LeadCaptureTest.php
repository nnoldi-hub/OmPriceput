<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Installation;
use App\Models\User;
use App\Notifications\NewLeadReceived;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_submission_creates_a_lead_and_notifies_sales(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Notification::fake();

        $response = $this->post(route('public.lead.store'), [
            'name' => 'Ion Popescu',
            'phone' => '0722123456',
            'email' => 'ion@example.com',
            'city' => 'Cluj-Napoca',
            'notes' => 'Interesat de pachetul Medium',
            'privacy_consent' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('clients', [
            'name' => 'Ion Popescu',
            'phone' => '0722123456',
            'source' => 'web',
            'status' => 'lead',
        ]);

        Notification::assertSentTo($admin, NewLeadReceived::class);
    }

    public function test_new_lead_notification_is_queued_and_points_to_the_appointment(): void
    {
        $client = Client::factory()->create();
        $visit = Installation::factory()->create([
            'client_id' => $client->id,
            'scheduled_at' => '2026-10-10 10:00',
        ]);

        $notification = new NewLeadReceived($client, $visit, ['Instalare centrală']);

        $this->assertInstanceOf(ShouldQueue::class, $notification);

        $mail = $notification->toMail($client);
        $this->assertSame('Vezi programarea', $mail->actionText);
        $this->assertSame(route('technical.installations.show', $visit), $mail->actionUrl);

        $this->assertSame([
            'client_id' => $client->id,
            'name' => $client->name,
            'phone' => $client->phone,
            'visit_id' => $visit->id,
            'scheduled_at' => '2026-10-10 10:00:00',
        ], $notification->toArray($client));
    }

    public function test_name_and_phone_are_required(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $response = $this->post(route('public.lead.store'), []);

        $response->assertSessionHasErrors(['name', 'phone']);
        $this->assertDatabaseCount('clients', 0);
    }

    public function test_invalid_email_is_rejected(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $response = $this->post(route('public.lead.store'), [
            'name' => 'Ion Popescu',
            'phone' => '0722123456',
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseCount(Client::class, 0);
    }

    public function test_repeated_submissions_are_rate_limited(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        User::factory()->create()->assignRole('admin');

        $payload = ['name' => 'Ion Popescu', 'phone' => '0722123456'];

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('public.lead.store'), $payload)->assertRedirect();
        }

        $this->post(route('public.lead.store'), $payload)->assertStatus(429);
    }
}
