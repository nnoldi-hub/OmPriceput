<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Installation;
use App\Models\Service;
use App\Models\User;
use App\Notifications\NewLeadReceived;
use App\Notifications\RequestConfirmation;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServiceSeeder;
use Database\Seeders\ToolboxSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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

    public function test_customer_receives_confirmation_email_when_email_is_provided(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        User::factory()->create()->assignRole('admin');
        config(['notifications.mail_enabled' => true]);

        Notification::fake();

        $this->post(route('public.lead.store'), [
            'name' => 'Ion Popescu',
            'phone' => '0722123456',
            'email' => 'ion@example.com',
            'privacy_consent' => '1',
        ])->assertRedirect();

        Notification::assertSentOnDemand(RequestConfirmation::class);
    }

    public function test_no_confirmation_email_without_email_address(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        User::factory()->create()->assignRole('admin');
        config(['notifications.mail_enabled' => true]);

        Notification::fake();

        $this->post(route('public.lead.store'), [
            'name' => 'Ion Popescu',
            'phone' => '0722123456',
            'privacy_consent' => '1',
        ])->assertRedirect();

        Notification::assertSentOnDemandTimes(RequestConfirmation::class, 0);
    }

    public function test_confirmation_email_shows_appointment_and_branding(): void
    {
        $client = Client::factory()->create();
        $visit = Installation::factory()->create([
            'client_id' => $client->id,
            'scheduled_at' => '2026-10-10 10:00',
        ]);

        $html = view('emails.request-received', [
            'recipientName' => $client->name,
            'companyName' => 'Om Priceput',
            'phone' => '0700 000 000',
            'hours' => 'Luni - Vineri, 08:00 - 19:00',
            'visit' => $visit,
            'services' => ['Instalare centrală'],
            'logoUrl' => asset('branding/logo-trim.png'),
        ])->render();

        $this->assertStringContainsString('10.10.2026 10:00', $html);
        $this->assertStringContainsString('Instalare centrală', $html);
        $this->assertStringContainsString('logo-trim.png', $html);
        $this->assertStringContainsString('am primit mesajul tău', $html);
    }

    public function test_quote_request_stores_uploaded_photos_with_the_appointment(): void
    {
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);
        User::factory()->create()->assignRole('admin');
        config(['notifications.mail_enabled' => true]);

        Notification::fake();

        $this->post(route('public.lead.store'), [
            'name' => 'Ion Popescu',
            'phone' => '0722123456',
            'job_type' => 'instalare',
            'photos' => [UploadedFile::fake()->image('hol.jpg', 800, 600)],
            'privacy_consent' => '1',
        ])->assertRedirect();

        $installation = Installation::firstOrFail();

        $this->assertCount(1, $installation->photos);
        $this->assertStringStartsWith('/storage/quote-requests/', $installation->photos[0]);

        $path = str_replace('/storage/', '', parse_url($installation->photos[0], PHP_URL_PATH));
        Storage::disk('public')->assertExists($path);
    }

    public function test_quote_request_appointment_notes_list_the_required_toolboxes(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ServiceSeeder::class);
        $this->seed(ToolboxSeeder::class);
        User::factory()->create()->assignRole('admin');

        Notification::fake();

        $service = Service::where('category', 'electric')->firstOrFail();

        $this->post(route('public.lead.store'), [
            'name' => 'Ion Popescu',
            'phone' => '0722123456',
            'job_type' => 'instalare',
            'service_ids' => [$service->id],
            'privacy_consent' => '1',
        ])->assertRedirect();

        $installation = Installation::firstOrFail();

        $this->assertStringContainsString('Constatare creată automat', $installation->notes);
        $this->assertStringContainsString('Cutii de luat: Master, Electric', $installation->notes);
        $this->assertStringNotContainsString('Cutii de luat', (string) $installation->customer_notes);
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
