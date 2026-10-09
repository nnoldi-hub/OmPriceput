<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::withCount('posts')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validateData($request));

        return back()->with('success', 'Categoria a fost adaugata.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validateData($request, $category));

        return back()->with('success', 'Categoria a fost actualizata.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return back()->with('success', 'Categoria a fost stearsa.');
    }

    private function validateData(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', 'unique:categories,slug,'.($category?->id ?? 'NULL')],
            'description' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', 'not_in:'.($category?->id ?? 'NULL')],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['name'], $category?->id);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['parent_id'] = ! empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        $data['description'] = ($data['description'] ?? null) ?: null;

        return $data;
    }

    private function uniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'categorie';
        $candidate = $base;
        $suffix = 2;

        while (Category::where('slug', $candidate)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
