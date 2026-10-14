<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Setting;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    private const PER_PAGE = 12;

    public function index(Request $request): Response
    {
        $service = trim($request->string('serviciu')->toString());

        $reviews = Review::published()
            ->when($service !== '', fn (Builder $query): Builder => $query->where('service_label', $service))
            ->latest('approved_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $reviews->through(fn (Review $review): array => [
            'name' => $review->displayName(),
            'city' => $review->city,
            'service' => $review->service_label,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'date' => $review->approved_at?->toDateString(),
        ]);

        $stats = $this->stats();

        app(Seo::class)->jsonLd([$this->reviewsSchema($reviews->items(), $stats)]);

        return Inertia::render('Public/Reviews', [
            'reviews' => $reviews,
            'services' => $this->services(),
            'stats' => $stats,
            'filters' => ['serviciu' => $service],
        ]);
    }

    /** @return Collection<int, string> */
    private function services(): Collection
    {
        return Review::published()
            ->whereNotNull('service_label')
            ->where('service_label', '!=', '')
            ->distinct()
            ->orderBy('service_label')
            ->pluck('service_label')
            ->values();
    }

    /** @return array{count: int, average: float, distribution: array<int, int>} */
    private function stats(): array
    {
        $ratings = Review::published()->pluck('rating');

        $distribution = collect([5, 4, 3, 2, 1])
            ->mapWithKeys(fn (int $star): array => [$star => $ratings->filter(fn ($value) => (int) $value === $star)->count()])
            ->all();

        return [
            'count' => $ratings->count(),
            'average' => round((float) ($ratings->avg() ?? 0), 1),
            'distribution' => $distribution,
        ];
    }

    /**
     * @param  array<int, array{name: string, rating: int, comment: ?string}>  $items
     * @param  array{count: int, average: float}  $stats
     * @return array<string, mixed>
     */
    private function reviewsSchema(array $items, array $stats): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'HomeAndConstructionBusiness',
            'name' => Setting::get('company_name') ?: config('app.name'),
            'url' => route('public.reviews.index'),
        ];

        if ($stats['count'] > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $stats['average'],
                'reviewCount' => $stats['count'],
                'bestRating' => 5,
                'worstRating' => 1,
            ];
            $schema['review'] = collect($items)->map(fn (array $review): array => [
                '@type' => 'Review',
                'author' => ['@type' => 'Person', 'name' => $review['name']],
                'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $review['rating'], 'bestRating' => 5],
                'reviewBody' => $review['comment'],
            ])->values()->all();
        }

        return $schema;
    }
}
