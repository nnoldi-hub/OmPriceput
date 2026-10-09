<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Tags/Index', [
            'tags' => Tag::withCount('posts')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Tag::create($this->validateData($request));

        return back()->with('success', 'Tag-ul a fost adaugat.');
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $tag->update($this->validateData($request, $tag));

        return back()->with('success', 'Tag-ul a fost actualizat.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return back()->with('success', 'Tag-ul a fost sters.');
    }

    private function validateData(Request $request, ?Tag $tag = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', 'unique:tags,slug,'.($tag?->id ?? 'NULL')],
        ]);

        $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['name'], $tag?->id);

        return $data;
    }

    private function uniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'tag';
        $candidate = $base;
        $suffix = 2;

        while (Tag::where('slug', $candidate)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
