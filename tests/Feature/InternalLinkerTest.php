<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Support\InternalLinker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalLinkerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_links_first_occurrence_of_another_post_title(): void
    {
        $target = Post::factory()->create(['title' => 'Cum alegi un flex profesional', 'slug' => 'cum-alegi-un-flex-profesional']);
        $current = Post::factory()->create();

        $body = 'Astazi discutam cum alegi un flex profesional pentru casa ta.';

        $out = app(InternalLinker::class)->link($body, $current);

        $this->assertStringContainsString('<a href="'.route('public.blog.show', $target->slug).'">cum alegi un flex profesional</a>', $out);
    }

    public function test_it_does_not_link_text_already_inside_an_anchor(): void
    {
        Post::factory()->create(['title' => 'Cum alegi un flex profesional', 'slug' => 'cum-alegi-un-flex-profesional']);
        $current = Post::factory()->create();

        $body = '<p><a href="/alt-ceva">Cum alegi un flex profesional</a></p>';

        $out = app(InternalLinker::class)->link($body, $current);

        $this->assertSame(1, substr_count($out, '<a '));
    }

    public function test_it_ignores_short_titles(): void
    {
        Post::factory()->create(['title' => 'Flex scurt', 'slug' => 'flex-scurt']);
        $current = Post::factory()->create();

        $out = app(InternalLinker::class)->link('Avem nevoie de flex scurt aici.', $current);

        $this->assertStringNotContainsString('<a ', $out);
    }

    public function test_it_respects_the_link_limit(): void
    {
        $titles = [];
        for ($i = 0; $i < 3; $i++) {
            $title = 'Titlu lung articol numarul '.$i.' pentru test';
            Post::factory()->create(['title' => $title, 'slug' => 'titlu-lung-'.$i]);
            $titles[] = $title;
        }
        $current = Post::factory()->create();

        $out = app(InternalLinker::class)->link(implode(' si ', $titles), $current, 1);

        $this->assertSame(1, substr_count($out, '<a '));
    }
}
