<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Pages/Index', ['pages' => Page::withCount('sections')->orderBy('title')->get()]);
    }

    public function edit(Page $page): Response
    {
        $page->load('sections');

        return Inertia::render('Admin/Pages/Edit', ['page' => $page]);
    }

    public function preview(Page $page): Response
    {
        $page->load('sections');

        return Inertia::render('Admin/Pages/Preview', ['page' => $page]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:draft,published'],
            'content' => ['nullable', 'json'],
            'sections' => ['nullable', 'array'],
            'sections.*.section_key' => ['required', 'in:hero,stats,packages,process,blog,cta,text_image,gallery,benefits,html'],
            'sections.*.content' => ['nullable'],
            'sections.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $page->update([
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'status' => $data['status'],
            'content' => $this->sanitizeContent($this->decodeJson($data['content'] ?? null, 'Continutul paginii')),
        ]);

        $page->sections()->delete();
        foreach ($data['sections'] ?? [] as $section) {
            $page->sections()->create([
                'section_key' => $section['section_key'],
                'content' => $this->normalizeSectionContent($section['content'] ?? null, $section['section_key']),
                'sort_order' => $section['sort_order'],
            ]);
        }

        return redirect()->route('admin.pages.index')->with('success', 'Pagina a fost actualizata.');
    }

    private function decodeJson(?string $value, string $label): ?array
    {
        if (blank($value)) {
            return null;
        }

        $decoded = json_decode($value, true);
        if (! is_array($decoded)) {
            abort(422, $label.' trebuie sa contina un JSON valid.');
        }

        return $decoded;
    }

    private function sanitizeContent(?array $content): ?array
    {
        if ($content === null) {
            return null;
        }

        if (isset($content['body_html'])) {
            $content['body_html'] = strip_tags((string) $content['body_html'], '<p><br><strong><em><ul><ol><li><a>');
        }
        if (isset($content['html'])) {
            $content['html'] = strip_tags((string) $content['html'], '<p><br><strong><em><ul><ol><li><a><div><span><section><h1><h2><h3>');
        }

        return $content;
    }

    private function normalizeSectionContent(mixed $content, string $sectionKey): ?array
    {
        if (blank($content)) {
            return null;
        }

        if (is_string($content)) {
            $content = $this->decodeJson($content, 'Sectiunea paginii');
        }

        if (is_array($content)) {
            $content['type'] ??= $sectionKey;

            return $this->sanitizeContent($content);
        }

        abort(422, 'Continutul sectiunii trebuie sa fie valid.');
    }
}
