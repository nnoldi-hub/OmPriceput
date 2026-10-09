<?php

namespace App\Console\Commands;

use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class OptimizeImages extends Command
{
    protected $signature = 'images:optimize
        {--disk=public : Storage disk to scan}
        {--min-bytes=102400 : Only touch files at least this large}
        {--max-width=1600 : Maximum width in pixels}
        {--dry-run : List what would change without writing}';

    protected $description = 'Convert uploaded images to optimized WebP and update database references';

    public function handle(): int
    {
        $disk = (string) $this->option('disk');
        $minBytes = (int) $this->option('min-bytes');
        $maxWidth = (int) $this->option('max-width');
        $dry = (bool) $this->option('dry-run');

        $storage = Storage::disk($disk);
        $optimizer = new ImageOptimizer($maxWidth);

        $map = [];
        $converted = 0;
        $savedBytes = 0;

        foreach ($storage->allFiles() as $relative) {
            $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));

            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                continue;
            }

            $absolute = $storage->path($relative);
            $info = @getimagesize($absolute);

            if ($info === false) {
                continue;
            }

            $bytes = (int) filesize($absolute);
            $width = (int) $info[0];

            $isAlreadyWebp = $extension === 'webp';
            $needsWork = ! $isAlreadyWebp || $width > $maxWidth || $bytes >= $minBytes;

            if (! $needsWork) {
                continue;
            }

            if ($dry) {
                $this->line(sprintf('DRY  %s  (%dpx, %d KB)', $relative, $width, intdiv($bytes, 1024)));

                continue;
            }

            $target = $optimizer->optimizeFile($relative, $disk);

            if ($target === null || $target === $relative) {
                continue;
            }

            $map[$relative] = $target;
            $converted++;

            $newBytes = (int) filesize($storage->path($target));
            $savedBytes += max(0, $bytes - $newBytes);

            $this->line(sprintf(
                'OK   %s -> %s  (%d KB -> %d KB)',
                $relative,
                $target,
                intdiv($bytes, 1024),
                intdiv($newBytes, 1024),
            ));
        }

        if ($dry) {
            $this->info('Dry run finished. No changes written.');

            return self::SUCCESS;
        }

        if ($map !== []) {
            $this->updateMediaMetadata($map, $disk);
            $this->updateReferences($map);
        }

        $this->info(sprintf(
            'Done. Converted: %d file(s). Saved: %.2f MB.',
            $converted,
            $savedBytes / 1024 / 1024,
        ));

        return self::SUCCESS;
    }

    /** @param  array<string, string>  $map */
    private function updateMediaMetadata(array $map, string $disk): void
    {
        if (! Schema::hasTable('media')) {
            return;
        }

        foreach (DB::table('media')->orderBy('id')->get() as $row) {
            if (! isset($map[$row->path])) {
                continue;
            }

            $absolute = Storage::disk($disk)->path($map[$row->path]);
            $info = @getimagesize($absolute);

            DB::table('media')->where('id', $row->id)->update([
                'mime_type' => 'image/webp',
                'size' => is_file($absolute) ? filesize($absolute) : $row->size,
                'width' => $info[0] ?? $row->width,
                'height' => $info[1] ?? $row->height,
            ]);
        }
    }

    /** @param  array<string, string>  $map */
    private function updateReferences(array $map): void
    {
        $search = [];
        $replace = [];

        foreach ($map as $old => $new) {
            $search[] = basename($old);
            $replace[] = basename($new);
        }

        $targets = [
            'posts' => ['cover_image', 'og_image', 'twitter_image', 'body'],
            'equipment' => ['image_path'],
            'installations' => ['photos', 'technician_signature', 'customer_signature'],
            'page_sections' => ['content'],
            'pages' => ['content'],
            'media' => ['path'],
        ];

        foreach ($targets as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = array_values(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn($table, $column),
            ));

            if ($columns === []) {
                continue;
            }

            $updated = 0;

            DB::table($table)
                ->select(array_merge(['id'], $columns))
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($table, $columns, $search, $replace, &$updated): void {
                    foreach ($rows as $row) {
                        $changes = [];

                        foreach ($columns as $column) {
                            $value = $row->{$column};

                            if (! is_string($value) || $value === '') {
                                continue;
                            }

                            $newValue = str_replace($search, $replace, $value);

                            if ($newValue !== $value) {
                                $changes[$column] = $newValue;
                            }
                        }

                        if ($changes !== []) {
                            DB::table($table)->where('id', $row->id)->update($changes);
                            $updated += count($changes);
                        }
                    }
                });

            if ($updated > 0) {
                $this->line("     references updated in {$table}: {$updated}");
            }
        }
    }
}
