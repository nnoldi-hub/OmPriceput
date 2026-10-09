<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    posts: Object,
    heading: { type: String, default: 'Blog' },
    description: { type: String, default: 'Ghiduri si sfaturi pentru intretinerea locuintei si a instalatiilor din casa sau apartament.' },
});

function formatDate(value) {
    return new Date(value).toLocaleDateString('ro-RO', { day: 'numeric', month: 'long', year: 'numeric' });
}
</script>

<template>
    <SeoHead />

    <PublicLayout>
        <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="font-display text-3xl font-bold text-slate-900">{{ heading }}</h1>
                <p class="mt-3 text-slate-500">{{ description }}</p>
            </div>

            <div v-if="!posts.data.length" class="mt-10 rounded-xl border border-dashed border-slate-300 p-10 text-center text-slate-400">
                Nu există articole de afișat.
            </div>

            <div v-else class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-2">
                <Link
                    v-for="post in posts.data"
                    :key="post.slug"
                    :href="route('public.blog.show', post.slug)"
                    class="group block overflow-hidden rounded-xl border border-slate-200 hover:border-blue-300 hover:shadow-sm"
                >
                    <img v-if="post.cover_image_url" :src="post.cover_image_url" :alt="post.cover_image_alt || post.title" class="h-48 w-full object-cover" loading="lazy" />
                    <div class="p-6">
                        <time class="text-xs text-slate-400">{{ formatDate(post.published_at) }}</time>
                        <h2 class="mt-2 text-lg font-semibold text-slate-900 group-hover:text-blue-700">{{ post.title }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ post.excerpt }}</p>
                        <div v-if="post.categories?.length" class="mt-3 flex flex-wrap gap-2">
                            <span v-for="category in post.categories" :key="category.id" class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ category.name }}</span>
                        </div>
                    </div>
                </Link>
            </div>

            <div v-if="posts.links.length > 3" class="mt-10 flex flex-wrap justify-center gap-2">
                <Link
                    v-for="link in posts.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[
                        'rounded-md px-3 py-1.5 text-sm',
                        link.active ? 'bg-blue-600 text-white' : 'text-slate-500 hover:bg-slate-100',
                        !link.url ? 'pointer-events-none opacity-40' : '',
                    ]"
                    v-html="link.label"
                />
            </div>
        </section>
    </PublicLayout>
</template>
