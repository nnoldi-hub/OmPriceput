<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Installation;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'abcdefghijklmnopqrstuvwx';

    private User $techUser;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->techUser = User::factory()->create();
        $this->techUser->assignRole('tehnic');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function review(array $attributes = []): Review
    {
        return Review::factory()->create(array_merge(['token' => self::TOKEN], $attributes));
    }

    public function test_requesting_a_review_creates_a_pending_entry_and_sends_an_sms(): void
    {
        $client = Client::factory()->create(['phone' => '0733111222']);
        $installation = Installation::factory()->create(['client_id' => $client->id, 'type' => 'reparatie']);

        $this->actingAs($this->techUser)
            ->post(route('technical.installations.review', $installation))
            ->assertRedirect();

        $review = Review::firstWhere('installation_id', $installation->id);
        $this->assertNotNull($review);
        $this->assertSame(Review::STATUS_PENDING, $review->status);
        $this->assertSame(24, strlen($review->token));
        $this->assertNotNull($review->requested_at);
        $this->assertDatabaseHas('sms_logs', ['phone' => '0733111222']);
    }

    public function test_requesting_a_review_twice_reuses_the_same_link(): void
    {
        $client = Client::factory()->create(['phone' => '0733111222']);
        $installation = Installation::factory()->create(['client_id' => $client->id]);

        $this->actingAs($this->techUser)->post(route('technical.installations.review', $installation));
        $token = Review::firstWhere('installation_id', $installation->id)->token;

        $this->actingAs($this->techUser)->post(route('technical.installations.review', $installation));

        $this->assertDatabaseCount('reviews', 1);
        $this->assertSame($token, Review::firstWhere('installation_id', $installation->id)->token);
    }

    public function test_requesting_a_review_without_a_valid_phone_still_creates_the_link(): void
    {
        $client = Client::factory()->create(['phone' => null]);
        $installation = Installation::factory()->create(['client_id' => $client->id]);

        $this->actingAs($this->techUser)
            ->post(route('technical.installations.review', $installation))
            ->assertSessionHas('success');

        $this->assertSame(Review::STATUS_PENDING, Review::firstWhere('installation_id', $installation->id)->status);
        $this->assertDatabaseCount('sms_logs', 0);
    }

    public function test_requesting_a_review_is_blocked_after_the_client_answered(): void
    {
        $installation = Installation::factory()->create();
        $this->review(['installation_id' => $installation->id, 'status' => Review::STATUS_SUBMITTED]);

        $this->actingAs($this->techUser)
            ->post(route('technical.installations.review', $installation))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_feedback_page_shows_the_form_and_is_not_indexed(): void
    {
        $this->review();

        $response = $this->get(route('review.feedback', self::TOKEN));

        $response->assertOk();
        $response->assertSee('Cum a fost lucrarea?');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_unknown_or_malformed_token_returns_404(): void
    {
        $this->get('/recenzie/'.str_repeat('a', 24))->assertNotFound();
        $this->get('/recenzie/short')->assertNotFound();
    }

    public function test_submitting_feedback_stores_the_rating_and_comment(): void
    {
        $review = $this->review();

        $this->post(route('review.feedback.store', self::TOKEN), [
            'rating' => 5,
            'comment' => 'Foarte multumit, a lucrat curat.',
            'author_name' => 'Andrei M.',
            'consent_to_publish' => '1',
        ])->assertRedirect(route('review.feedback', self::TOKEN));

        $review->refresh();
        $this->assertSame(Review::STATUS_SUBMITTED, $review->status);
        $this->assertSame(5, $review->rating);
        $this->assertSame('Foarte multumit, a lucrat curat.', $review->comment);
        $this->assertTrue($review->consent_to_publish);
        $this->assertNotNull($review->submitted_at);
    }

    public function test_feedback_requires_a_rating(): void
    {
        $review = $this->review();

        $this->post(route('review.feedback.store', self::TOKEN), ['comment' => 'ok'])
            ->assertSessionHasErrors('rating');

        $this->assertSame(Review::STATUS_PENDING, $review->refresh()->status);
    }

    public function test_feedback_cannot_be_submitted_twice(): void
    {
        $review = $this->review([
            'status' => Review::STATUS_SUBMITTED,
            'rating' => 4,
        ]);

        $this->post(route('review.feedback.store', self::TOKEN), ['rating' => 1])
            ->assertSessionHas('status');

        $this->assertSame(4, $review->refresh()->rating);
    }

    public function test_approved_review_appears_on_the_homepage_but_pending_does_not(): void
    {
        $this->review([
            'status' => Review::STATUS_APPROVED,
            'approved_at' => now(),
            'consent_to_publish' => true,
            'rating' => 5,
            'author_name' => 'Andrei',
            'comment' => 'Excelent.',
        ]);
        $this->review(['status' => Review::STATUS_SUBMITTED, 'token' => 'zzzzzzzzzzzzzzzzzzzzzzzz']);

        $this->get(route('public.home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Home')
                ->has('reviews', 1)
                ->where('reviews.0.name', 'Andrei')
                ->where('reviewStats.count', 1)
                ->where('reviewStats.average', 5)
            );
    }

    public function test_an_approved_review_without_consent_is_not_published(): void
    {
        $this->review([
            'status' => Review::STATUS_APPROVED,
            'approved_at' => now(),
            'consent_to_publish' => false,
            'author_name' => 'Anonim',
        ]);

        $this->get(route('public.home'))
            ->assertInertia(fn ($page) => $page->has('reviews', 0));
    }

    public function test_admin_can_approve_a_review(): void
    {
        $review = $this->review(['status' => Review::STATUS_SUBMITTED, 'rating' => 5]);

        $this->actingAs($this->admin)
            ->patch(route('admin.reviews.approve', $review))
            ->assertRedirect();

        $review->refresh();
        $this->assertSame(Review::STATUS_APPROVED, $review->status);
        $this->assertNotNull($review->approved_at);
        $this->assertSame($this->admin->id, $review->approved_by);
    }

    public function test_admin_can_reject_a_review(): void
    {
        $review = $this->review(['status' => Review::STATUS_SUBMITTED]);

        $this->actingAs($this->admin)
            ->patch(route('admin.reviews.reject', $review))
            ->assertRedirect();

        $this->assertSame(Review::STATUS_REJECTED, $review->refresh()->status);
    }

    public function test_admin_can_update_a_review(): void
    {
        $review = $this->review(['status' => Review::STATUS_SUBMITTED, 'comment' => 'vechi']);

        $this->actingAs($this->admin)
            ->put(route('admin.reviews.update', $review), [
                'author_name' => 'Ion P.',
                'city' => 'Bucuresti',
                'service_label' => 'Montaj mobilier',
                'rating' => 4,
                'comment' => 'Text editat.',
            ])
            ->assertRedirect();

        $review->refresh();
        $this->assertSame('Text editat.', $review->comment);
        $this->assertSame(4, $review->rating);
    }

    public function test_admin_can_view_the_reviews_index(): void
    {
        $this->review();

        $this->actingAs($this->admin)
            ->get(route('admin.reviews.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Reviews/Index')
                ->has('reviews.data', 1)
            );
    }

    public function test_non_admin_cannot_moderate_reviews(): void
    {
        $this->actingAs($this->techUser)
            ->get(route('admin.reviews.index'))
            ->assertForbidden();
    }

    public function test_the_token_is_hidden_from_serialization(): void
    {
        $this->assertArrayNotHasKey('token', $this->review()->toArray());
    }

    public function test_the_public_reviews_page_lists_only_published_reviews(): void
    {
        $this->review([
            'status' => Review::STATUS_APPROVED,
            'approved_at' => now(),
            'consent_to_publish' => true,
            'rating' => 5,
            'author_name' => 'Andrei',
            'service_label' => 'Montaj mobilier',
        ]);
        $this->review(['status' => Review::STATUS_PENDING, 'token' => 'zzzzzzzzzzzzzzzzzzzzzzzz']);
        $this->review(['status' => Review::STATUS_APPROVED, 'approved_at' => now(), 'consent_to_publish' => false, 'token' => 'yyyyyyyyyyyyyyyyyyyyyyyy']);

        $this->get(route('public.reviews.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Reviews')
                ->has('reviews.data', 1)
                ->where('reviews.data.0.name', 'Andrei')
                ->where('stats.count', 1)
                ->where('stats.average', 5)
                ->where('stats.distribution.5', 1)
            );
    }

    public function test_the_public_reviews_page_can_be_filtered_by_service(): void
    {
        $this->review(['status' => Review::STATUS_APPROVED, 'approved_at' => now(), 'consent_to_publish' => true, 'service_label' => 'Montaj mobilier', 'token' => 'aaaaaaaaaaaaaaaaaaaaaaaa']);
        $this->review(['status' => Review::STATUS_APPROVED, 'approved_at' => now(), 'consent_to_publish' => true, 'service_label' => 'Reparatii electrice', 'token' => 'bbbbbbbbbbbbbbbbbbbbbbbb']);

        $this->get(route('public.reviews.index', ['serviciu' => 'Montaj mobilier']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('reviews.data', 1)
                ->where('reviews.data.0.service', 'Montaj mobilier')
                ->where('stats.count', 2)
            );
    }

    public function test_the_reviews_page_is_listed_in_the_sitemap(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('public.reviews.index'), false);
    }

    public function test_robots_disallows_the_review_path(): void
    {
        $this->get(route('robots'))->assertOk()->assertSee('Disallow: /recenzie/');
    }
}
