<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Support\ImageOptimizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Media/Index', [
            'media' => Media::latest()->paginate(24),
        ]);
    }

    public function json(): JsonResponse
    {
        return response()->json([
            'data' => Media::latest()->take(200)->get(['id', 'path', 'alt', 'title', 'original_name']),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'file' => ['required_without:files', 'file', 'image', 'max:8192'],
            'files' => ['required_without:file', 'array', 'max:20'],
            'files.*' => ['file', 'image', 'max:8192'],
        ]);

        $files = $request->hasFile('files') ? $request->file('files') : [$request->file('file')];

        $created = [];
        $optimizer = app(ImageOptimizer::class);
        $disk = Storage::disk('public');

        foreach ($files as $file) {
            $path = $optimizer->store($file, 'media', 'public');
            [$width, $height] = $this->dimensions($path);

            $created[] = Media::create([
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $disk->mimeType($path) ?: 'image/webp',
                'size' => $disk->size($path),
                'width' => $width,
                'height' => $height,
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'uploaded_by' => $request->user()->id,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'location' => $created[0]->url,
                'media' => $created,
            ]);
        }

        return back()->with('success', count($created) === 1 ? 'Imaginea a fost incarcata.' : count($created).' imagini au fost incarcate.');
    }

    public function update(Request $request, Media $media): RedirectResponse
    {
        $data = $request->validate([
            'alt' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $data['alt'] = ($data['alt'] ?? null) ?: null;
        $data['title'] = ($data['title'] ?? null) ?: null;

        $media->update($data);

        return back()->with('success', 'Detaliile imaginii au fost actualizate.');
    }

    public function destroy(Media $media): RedirectResponse
    {
        Storage::disk($media->disk ?: 'public')->delete($media->path);

        $media->delete();

        return back()->with('success', 'Imaginea a fost stearsa.');
    }

    /** @return array{0: ?int, 1: ?int} */
    private function dimensions(string $path): array
    {
        try {
            $size = @getimagesize(Storage::disk('public')->path($path));

            return $size ? [(int) $size[0], (int) $size[1]] : [null, null];
        } catch (\Throwable) {
            return [null, null];
        }
    }
}
