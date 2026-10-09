<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    post: Object,
    related: Array,
});

function formatDate(value) {
    return new Date(value).toLocaleDateString('ro-RO', { day: 'numeric', month: 'long', year: 'numeric' });
}

const readingMinutes = computed(() => {
    const text = (props.post.body || '').replace(/<[^>]*>/g, ' ');
    const words = text.trim().split(/\s+/).filter(Boolean).length;
    return Math.max(1, Math.round(words / 200));
});
</script>

<template>
    <SeoHead />

    <PublicLayout>
        <article class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
            <Link :href="route('public.blog.index')" class="text-sm font-semibold text-blue-600 hover:text-blue-500">
                &larr; Înapoi la blog
            </Link>

            <div v-if="post.categories?.length" class="mt-6 flex flex-wrap gap-2">
                <Link
                    v-for="category in post.categories"
                    :key="category.id"
                    :href="route('public.blog.category', category.slug)"
                    class="inline-flex min-h-[44px] min-w-[48px] items-center justify-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                >
                    {{ category.name }}
                </Link>
            </div>

            <h1 class="mt-4 font-display text-3xl font-bold text-slate-900 sm:text-4xl">{{ post.title }}</h1>

            <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-400">
                <time>{{ formatDate(post.published_at) }}</time>
                <span v-if="post.author">de {{ post.author.name }}</span>
                <span>{{ readingMinutes }} min de citit</span>
            </div>

            <img
                v-if="post.cover_image_url"
                :src="post.cover_image_url"
                :alt="post.cover_image_alt || post.title"
                class="mt-8 w-full rounded-xl object-cover"
            />
            <p v-if="post.cover_image_caption" class="mt-2 text-center text-xs text-slate-400">
                {{ post.cover_image_caption }}
                <span v-if="post.cover_image_credit">· Foto: {{ post.cover_image_credit }}</span>
            </p>

            <div class="prose prose-slate mt-8 max-w-none text-slate-600 prose-headings:font-display prose-headings:font-bold prose-headings:text-slate-900 prose-a:text-blue-600 prose-a:no-underline hover:prose-a:underline" v-html="post.body_html"></div>

            <div v-if="post.gallery_images?.length" class="mt-10">
                <h2 class="text-lg font-semibold text-slate-900">Galerie foto</h2>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <a
                        v-for="image in post.gallery_images"
                        :key="image.id"
                        :href="image.url"
                        target="_blank"
                        class="overflow-hidden rounded-lg border border-slate-200"
                    >
                        <img :src="image.url" :alt="image.alt || post.title" class="h-40 w-full object-cover transition hover:scale-105" loading="lazy" />
                    </a>
                </div>
            </div>

            <div v-if="post.tags?.length" class="mt-10 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-6">
                <span class="text-sm font-medium text-slate-500">Etichete:</span>
                <Link
                    v-for="tag in post.tags"
                    :key="tag.id"
                    :href="route('public.blog.tag', tag.slug)"
                    class="inline-flex min-h-[44px] min-w-[48px] items-center justify-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 hover:bg-slate-200"
                >
                    #{{ tag.name }}
                </Link>
            </div>

            <section v-if="post.faq?.length" class="mt-10">
                <h2 class="text-xl font-semibold text-slate-900">Întrebări frecvente</h2>
                <div class="mt-4 space-y-3">
                    <details v-for="(item, index) in post.faq" :key="index" class="group rounded-lg border border-slate-200 bg-white p-4">
                        <summary class="flex min-h-[44px] cursor-pointer items-center text-base font-medium text-slate-800 marker:content-['']">
                            {{ item.question }}
                        </summary>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ item.answer }}</p>
                    </details>
                </div>
            </section>
        </article>

        <section v-if="related.length" class="bg-slate-50 py-16">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-xl font-semibold text-slate-900">Articole similare</h2>
                <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <Link
                        v-for="item in related"
                        :key="item.slug"
                        :href="route('public.blog.show', item.slug)"
                        class="group block overflow-hidden rounded-xl border border-slate-200 bg-white hover:border-blue-300 hover:shadow-sm"
                    >
                        <img v-if="item.cover_image_url" :src="item.cover_image_url" :alt="item.title" class="h-32 w-full object-cover" loading="lazy" />
                        <div class="p-5">
                            <h3 class="font-semibold text-slate-900 group-hover:text-blue-700">{{ item.title }}</h3>
                            <p class="mt-2 text-sm text-slate-500">{{ item.excerpt }}</p>
                        </div>
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
