<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $reviews = Review::query()
            ->with(['client:id,name,phone', 'installation:id,type'])
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderByRaw("case status when 'submitted' then 0 when 'pending' then 1 when 'approved' then 2 else 3 end")
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => $request->only('status'),
            'statuses' => Review::STATUSES,
            'summary' => [
                'pending' => Review::where('status', Review::STATUS_SUBMITTED)->count(),
                'approved' => Review::where('status', Review::STATUS_APPROVED)->count(),
                'average' => round((float) Review::approved()->avg('rating'), 1),
            ],
        ]);
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate([
            'author_name' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'service_label' => ['nullable', 'string', 'max:255'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review->update($data);

        return back()->with('success', 'Recenzie actualizata.');
    }

    public function approve(Request $request, Review $review): RedirectResponse
    {
        $review->update([
            'status' => Review::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Recenzie publicata pe site.');
    }

    public function reject(Review $review): RedirectResponse
    {
        $review->update([
            'status' => Review::STATUS_REJECTED,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return back()->with('success', 'Recenzie respinsa.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return back()->with('success', 'Recenzie stearsa.');
    }
}
