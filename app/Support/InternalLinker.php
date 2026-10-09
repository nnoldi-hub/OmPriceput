<?php

namespace App\Support;

use App\Models\Post;

class InternalLinker
{
    /**
     * Wrap the first plain-text occurrence of other published post titles
     * inside anchor tags. Text that is already part of an anchor is skipped.
     */
    public function link(string $html, ?Post $current = null, int $limit = 5): string
    {
        if ($html === '' || $limit < 1) {
            return $html;
        }

        $candidates = $this->candidates($current);

        if ($candidates === []) {
            return $html;
        }

        $parts = preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return $html;
        }

        $linked = 0;
        $inAnchor = false;

        foreach ($parts as $index => $part) {
            if ($part === '') {
                continue;
            }

            if ($part[0] === '<') {
                if (preg_match('/^<a[\s>]/i', $part)) {
                    $inAnchor = true;
                } elseif (preg_match('/^<\/a>/i', $part)) {
                    $inAnchor = false;
                }

                continue;
            }

            if ($inAnchor) {
                continue;
            }

            foreach ($candidates as $title => $url) {
                if ($linked >= $limit) {
                    break 2;
                }

                $pattern = '/(?<![\p{L}\p{N}])'.preg_quote($title, '/').'(?![\p{L}\p{N}])/iu';

                $replaced = preg_replace_callback(
                    $pattern,
                    fn (array $matches) => '<a href="'.$url.'">'.$matches[0].'</a>',
                    $parts[$index],
                    1,
                    $count,
                );

                if ($replaced !== null && $count > 0) {
                    $parts[$index] = $replaced;
                    $linked++;
                    unset($candidates[$title]);
                    break;
                }
            }
        }

        return implode('', $parts);
    }

    /**
     * @return array<string, string>
     */
    private function candidates(?Post $current): array
    {
        return Post::published()
            ->when($current && $current->exists, fn ($query) => $query->whereKeyNot($current->getKey()))
            ->latest('published_at')
            ->get(['id', 'title', 'slug'])
            ->filter(fn (Post $post) => $this->isLinkable($post->title))
            ->sortByDesc(fn (Post $post) => mb_strlen($post->title))
            ->mapWithKeys(fn (Post $post) => [
                $post->title => route('public.blog.show', $post->slug),
            ])
            ->all();
    }

    private function isLinkable(string $title): bool
    {
        $title = trim($title);

        if (mb_strlen($title) < 15) {
            return false;
        }

        return count(preg_split('/\s+/', $title, -1, PREG_SPLIT_NO_EMPTY)) >= 3;
    }
}
