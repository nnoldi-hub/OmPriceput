<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Pagina publică (fără cont) prin care clientul lasă o recenzie, deschisă
 * din linkul cu token primit după finalizarea lucrării.
 */
class ReviewFeedbackController extends Controller
{
    public function show(string $token): Response
    {
        $review = $this->find($token);

        return response()
            ->view('reviews.feedback', [
                'review' => $review,
                'token' => $token,
            ])
            ->header('Cache-Control', 'no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $review = $this->find($token);

        if ($review->status !== Review::STATUS_PENDING) {
            return redirect()
                ->route('review.feedback', $token)
                ->with('status', 'Recenzia a fost deja trimisă. Îți mulțumim!');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'consent_to_publish' => ['nullable', 'boolean'],
        ]);

        $review->update([
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'author_name' => $data['author_name'] ?? null,
            'consent_to_publish' => $request->boolean('consent_to_publish'),
            'status' => Review::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'ip' => $request->ip(),
        ]);

        return redirect()
            ->route('review.feedback', $token)
            ->with('status', 'Îți mulțumim pentru părere! O vom publica după o scurtă verificare.');
    }

    private function find(string $token): Review
    {
        abort_unless(Str::length($token) === 24, 404);

        return Review::query()
            ->with(['client:id,name', 'installation:id,type'])
            ->where('token', $token)
            ->firstOrFail();
    }
}
