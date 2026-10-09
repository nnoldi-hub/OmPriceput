<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageOptimizer
{
    public function __construct(
        private readonly int $maxWidth = 1600,
        private readonly int $quality = 82,
        private readonly int $maxPixels = 24_000_000,
    ) {}

    /**
     * Store an uploaded image on the given disk as an optimized WebP file.
     * Falls back to a plain store for formats we do not re-encode (e.g. animated GIF).
     */
    public function store(UploadedFile $file, string $folder, string $disk = 'public'): string
    {
        $webp = $this->toWebp($file->getRealPath());

        if ($webp === null) {
            return $file->store($folder, $disk);
        }

        $name = pathinfo($file->hashName(), PATHINFO_FILENAME).'.webp';
        $path = trim($folder, '/').'/'.$name;

        Storage::disk($disk)->put($path, $webp);

        return $path;
    }

    /**
     * Re-encode an existing file in place, keeping its basename but switching to WebP.
     * Returns the new relative path, or null when the file was skipped.
     */
    public function optimizeFile(string $relativePath, string $disk = 'public'): ?string
    {
        $storage = Storage::disk($disk);

        if (! $storage->exists($relativePath)) {
            return null;
        }

        $webp = $this->toWebp($storage->path($relativePath));

        if ($webp === null) {
            return null;
        }

        $dir = dirname($relativePath);
        $base = pathinfo($relativePath, PATHINFO_FILENAME);
        $target = ($dir === '.' ? '' : $dir.'/').$base.'.webp';

        $storage->put($target, $webp);

        if ($target !== $relativePath) {
            $storage->delete($relativePath);
        }

        return $target;
    }

    /**
     * Read an image from disk and return optimized WebP bytes, or null if unsupported.
     */
    public function toWebp(string $absolutePath): ?string
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        $info = @getimagesize($absolutePath);

        if ($info === false) {
            return null;
        }

        [$width, $height] = $info;
        $mime = $info['mime'] ?? '';

        // Keep animated GIFs untouched.
        if ($mime === 'image/gif') {
            return null;
        }

        if (! function_exists('imagewebp')) {
            return null;
        }

        if ($mime === 'image/webp' && ! function_exists('imagecreatefromwebp')) {
            return null;
        }

        if ($width * $height > $this->maxPixels) {
            return null;
        }

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($absolutePath),
            'image/png' => @imagecreatefrompng($absolutePath),
            'image/webp' => @imagecreatefromwebp($absolutePath),
            default => null,
        };

        if ($image === false || $image === null) {
            return null;
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        if ($width > $this->maxWidth) {
            $newWidth = $this->maxWidth;
            $newHeight = max(1, (int) round($height * ($this->maxWidth / $width)));
            $scaled = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($scaled, false);
            imagesavealpha($scaled, true);
            imagecopyresampled($scaled, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $scaled;
        }

        ob_start();
        $ok = imagewebp($image, null, $this->quality);
        $data = ob_get_clean();
        imagedestroy($image);

        if (! $ok || $data === false || $data === '') {
            return null;
        }

        return $data;
    }
}
