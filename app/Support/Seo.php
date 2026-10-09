<?php

namespace App\Support;

class Seo
{
    public ?string $title = null;

    public ?string $description = null;

    public ?string $image = null;

    public bool $indexed = false;

    public ?string $canonical = null;

    public ?string $robots = null;

    public string $type = 'website';

    public ?string $ogTitle = null;

    public ?string $ogDescription = null;

    public ?string $ogImage = null;

    public ?string $twitterTitle = null;

    public ?string $twitterDescription = null;

    public ?string $twitterImage = null;

    /** @var array<int, array<string, mixed>> */
    public array $jsonLd = [];

    public function reset(): void
    {
        $this->title = null;
        $this->description = null;
        $this->image = null;
        $this->indexed = false;
        $this->canonical = null;
        $this->robots = null;
        $this->type = 'website';
        $this->ogTitle = null;
        $this->ogDescription = null;
        $this->ogImage = null;
        $this->twitterTitle = null;
        $this->twitterDescription = null;
        $this->twitterImage = null;
        $this->jsonLd = [];
    }

    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function image(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function allowIndex(): static
    {
        $this->indexed = true;

        return $this;
    }

    public function noindex(): static
    {
        $this->indexed = false;

        return $this;
    }

    public function canonical(?string $canonical): static
    {
        $this->canonical = $canonical ?: null;

        return $this;
    }

    public function robots(?string $robots): static
    {
        $this->robots = $robots ?: null;

        return $this;
    }

    public function type(?string $type): static
    {
        $this->type = $type ?: 'website';

        return $this;
    }

    public function ogTitle(?string $title): static
    {
        $this->ogTitle = $title;

        return $this;
    }

    public function ogDescription(?string $description): static
    {
        $this->ogDescription = $description;

        return $this;
    }

    public function ogImage(?string $image): static
    {
        $this->ogImage = $image;

        return $this;
    }

    public function twitterTitle(?string $title): static
    {
        $this->twitterTitle = $title;

        return $this;
    }

    public function twitterDescription(?string $description): static
    {
        $this->twitterDescription = $description;

        return $this;
    }

    public function twitterImage(?string $image): static
    {
        $this->twitterImage = $image;

        return $this;
    }

    /** @param array<int, array<string, mixed>> $blocks */
    public function jsonLd(array $blocks): static
    {
        $this->jsonLd = array_merge($this->jsonLd, $blocks);

        return $this;
    }

    public function fullTitle(): string
    {
        $title = ($this->title !== null && $this->title !== '') ? $this->title : (string) config('app.name');
        $brand = (string) config('app.name');

        if ($brand !== '' && stripos($title, $brand) === false) {
            return $title.' - '.$brand;
        }

        return $title;
    }

    public function canonicalUrl(): string
    {
        return $this->canonical ?? request()->url();
    }

    public function robotsContent(): string
    {
        return $this->robots ?? ($this->indexed ? 'index,follow' : 'noindex,nofollow');
    }

    public function imageUrl(): string
    {
        return $this->ogImage ?? $this->image ?? asset('branding/logo-trim.png');
    }

    public function ogTitleValue(): string
    {
        return $this->ogTitle ?? $this->fullTitle();
    }

    public function ogDescriptionValue(): string
    {
        return $this->ogDescription ?? (string) $this->description;
    }

    public function twitterTitleValue(): string
    {
        return $this->twitterTitle ?? $this->fullTitle();
    }

    public function twitterDescriptionValue(): string
    {
        return $this->twitterDescription ?? (string) $this->description;
    }

    public function twitterImageUrl(): string
    {
        return $this->twitterImage ?? $this->imageUrl();
    }
}
