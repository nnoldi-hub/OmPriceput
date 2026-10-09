<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    posts: Object,
    featured: { type: Object, default: null },
    filters: { type: Object, default: () => ({ q: '', sort: 'newest', category: null, tag: null }) },
    sorts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    heading: { type: String, default: 'Blog' },
    description: { type: String, default: 'Ghiduri si sfaturi pentru intretinerea locuintei si a instalatiilor din casa sau apartament.' },
});

const search = ref(props.filters.q ?? '');

watch(() => props.filters.q, (value) => {
    search.value = value ?? '';
});

const hasFilters = computed(() => Boolean(
    props.filters.q
    || props.filters.category
    || props.filters.tag
    || (props.filters.sort && props.filters.sort !== 'newest'),
));

function params(overrides = {}) {
    const merged = {
        q: props.filters.q || undefined,
        sort: props.filters.sort && props.filters.sort !== 'newest' ? props.filters.sort : undefined,
        category: props.filters.category || undefined,
        tag: props.filters.tag || undefined,
        ...overrides,
    };

    return Object.fromEntries(Object.entries(merged).filter(([, value]) => value !== undefined && value !== null && value !== ''));
}

function url(overrides = {}) {
    return route('public.blog.index', params(overrides));
}

function submitSearch() {
    router.get(url({ q: search.value.trim() || undefined, page: undefined }), {}, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function changeSort(event) {
    const value = event.target.value;
    router.get(url({ sort: value === 'newest' ? undefined : value, page: undefined }), {}, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function toggleCategory(slug) {
    router.get(url({
        category: props.filters.category === slug ? undefined : slug,
        tag: undefined,
        page: undefined,
    }), {}, { preserveScroll: true, preserveState: true, replace: true });
}

function toggleTag(slug) {
    router.get(url({
        tag: props.filters.tag === slug ? undefined : slug,
        category: undefined,
        page: undefined,
    }), {}, { preserveScroll: true, preserveState: true, replace: true });
}

function formatDate(value) {
    return new Date(value).toLocaleDateString('ro-RO', { day: 'numeric', month: 'long', year: 'numeric' });
}
</script>

<template>
    <SeoHead />

    <PublicLayout>
        <section class="border-b border-slate-800 bg-[#021a2d] text-white">
            <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-400">Blog Om Priceput</p>
                    <h1 class="mt-3 font-display text-3xl font-bold sm:text-4xl">{{ heading }}</h1>
                    <p class="mt-3 text-slate-300">{{ description }}</p>
                </div>

                <form class="mt-8 flex flex-col gap-3 sm:flex-row" role="search" @submit.prevent="submitSearch">
                    <label class="sr-only" for="blog-search">Caută articole</label>
                    <div class="relative flex-1">
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
                        </svg>
                        <input
                            id="blog-search"
                            v-model="search"
                            type="search"
                            placeholder="Caută în articole (ex. parchet, boiler, prize)..."
                            class="min-h-[48px] w-full rounded-lg border-0 pl-12 pr-4 text-slate-900 shadow-sm placeholder:text-slate-400 focus:ring-2 focus:ring-orange-400"
                        />
                    </div>
                    <button type="submit" class="min-h-[48px] rounded-lg bg-orange-500 px-6 font-semibold text-white shadow-sm transition hover:bg-orange-400">
                        Caută
                    </button>
                </form>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-center lg:justify-between">
                <p class="text-sm text-slate-500">
                    <span class="font-semibold text-slate-900">{{ total }}</span>
                    {{ total === 1 ? 'articol' : 'articole' }}
                    <span v-if="filters.q"> pentru „<span class="text-slate-700">{{ filters.q }}</span>”</span>
                </p>

                <div class="flex flex-wrap items-center gap-3">
                    <label class="sr-only" for="blog-sort">Sortează</label>
                    <select
                        id="blog-sort"
                        :value="filters.sort"
                        class="min-h-[48px] rounded-lg border-slate-300 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500"
                        @change="changeSort"
                    >
                        <option v-for="option in sorts" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>

                    <Link
                        v-if="hasFilters"
                        :href="route('public.blog.index')"
                        class="inline-flex min-h-[48px] items-center rounded-lg px-3 text-sm font-medium text-slate-500 hover:bg-slate-100"
                    >
                        Resetează filtrele
                    </Link>
                </div>
            </div>

            <div v-if="categories.length || tags.length" class="mt-6 space-y-4">
                <div v-if="categories.length" class="flex flex-wrap items-center gap-2">
                    <span class="mr-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Categorii</span>
                    <button
                        v-for="category in categories"
                        :key="category.id"
                        type="button"
                        class="inline-flex min-h-[44px] min-w-[48px] items-center gap-1.5 rounded-full px-4 text-sm font-medium transition"
                        :class="filters.category === category.slug
                            ? 'bg-blue-600 text-white'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        @click="toggleCategory(category.slug)"
                    >
                        {{ category.name }}
                        <span class="text-xs opacity-70">{{ category.posts_count }}</span>
                    </button>
                </div>

                <div v-if="tags.length" class="flex flex-wrap items-center gap-2">
                    <span class="mr-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Etichete</span>
                    <button
                        v-for="tag in tags"
                        :key="tag.id"
                        type="button"
                        class="inline-flex min-h-[44px] min-w-[48px] items-center rounded-full px-4 text-sm font-medium transition"
                        :class="filters.tag === tag.slug
                            ? 'bg-slate-800 text-white'
                            : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-200 hover:bg-slate-50'"
                        @click="toggleTag(tag.slug)"
                    >
                        #{{ tag.name }}
                    </button>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6 lg:px-8">
            <Link
                v-if="featured"
                :href="route('public.blog.show', featured.slug)"
                class="group grid overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-blue-300 hover:shadow-md lg:grid-cols-2"
            >
                <div class="relative h-64 overflow-hidden bg-slate-100 lg:h-full">
                    <img
                        v-if="featured.cover_image_url"
                        :src="featured.cover_image_url"
                        :alt="featured.cover_image_alt || featured.title"
                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                        decoding="async"
                    />
                    <span v-else class="flex h-full items-center justify-center font-display text-4xl font-bold text-slate-300">Om Priceput</span>
                </div>
                <div class="flex flex-col justify-center p-8">
                    <span class="inline-flex w-fit items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-orange-700">Recomandat</span>
                    <h2 class="mt-4 font-display text-2xl font-bold text-slate-900 group-hover:text-blue-700 sm:text-3xl">{{ featured.title }}</h2>
                    <p class="mt-3 line-clamp-3 text-slate-500">{{ featured.excerpt }}</p>
                    <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-400">
                        <time>{{ formatDate(featured.published_at) }}</time>
                        <span v-if="featured.reading_minutes">{{ featured.reading_minutes }} min de citit</span>
                    </div>
                    <span class="mt-6 inline-flex items-center gap-2 font-semibold text-blue-700">
                        Citește articolul
                        <svg class="h-4 w-4 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0-4 4m4-4H3" />
                        </svg>
                    </span>
                </div>
            </Link>

            <div v-if="!posts.data.length && !featured" class="mt-10 rounded-xl border border-dashed border-slate-300 p-10 text-center text-slate-400">
                <p class="font-medium text-slate-600">Nu am găsit articole.</p>
                <p class="mt-1 text-sm">Încearcă alt termen sau resetează filtrele.</p>
            </div>

            <div v-else-if="posts.data.length" class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="post in posts.data"
                    :key="post.slug"
                    :href="route('public.blog.show', post.slug)"
                    class="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:border-blue-300 hover:shadow-md"
                >
                    <div class="relative h-48 overflow-hidden bg-slate-100">
                        <img
                            v-if="post.cover_image_url"
                            :src="post.cover_image_url"
                            :alt="post.cover_image_alt || post.title"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                            loading="lazy"
                            decoding="async"
                        />
                        <span v-else class="flex h-full items-center justify-center text-slate-300">
                            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4-4 4 4 4-8 4 8H4z" /></svg>
                        </span>
                        <span v-if="post.categories?.length" class="absolute left-3 top-3 rounded-full bg-white/95 px-3 py-1 text-xs font-semibold text-slate-700 shadow-sm">{{ post.categories[0].name }}</span>
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-400">
                            <time>{{ formatDate(post.published_at) }}</time>
                            <span v-if="post.reading_minutes">· {{ post.reading_minutes }} min</span>
                        </div>
                        <h2 class="mt-2 font-display text-lg font-semibold text-slate-900 group-hover:text-blue-700">{{ post.title }}</h2>
                        <p class="mt-2 line-clamp-3 flex-1 text-sm text-slate-500">{{ post.excerpt }}</p>
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-blue-700">
                            Citește
                            <svg class="h-4 w-4 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0-4 4m4-4H3" /></svg>
                        </span>
                    </div>
                </Link>
            </div>

            <div v-if="posts.links.length > 3" class="mt-12 flex flex-wrap justify-center gap-2">
                <Link
                    v-for="link in posts.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[
                        'inline-flex min-h-[48px] min-w-[48px] items-center justify-center rounded-md px-3 py-1.5 text-sm',
                        link.active ? 'bg-blue-600 text-white' : 'text-slate-500 hover:bg-slate-100',
                        !link.url ? 'pointer-events-none opacity-40' : '',
                    ]"
                    v-html="link.label"
                />
            </div>
        </section>
    </PublicLayout>
</template>
