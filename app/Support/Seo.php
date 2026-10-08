<?php

namespace App\Support;

class Seo
{
    public ?string $title = null;

    public ?string $description = null;

    public ?string $image = null;

    public bool $indexed = false;

    /** @var array<int, array<string, mixed>> */
    public array $jsonLd = [];

    public function reset(): void
    {
        $this->title = null;
        $this->description = null;
        $this->image = null;
        $this->indexed = false;
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
        return request()->url();
    }

    public function imageUrl(): string
    {
        return $this->image ?? asset('branding/logo-trim.png');
    }
}
