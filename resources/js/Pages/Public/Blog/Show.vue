<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    post: Object,
    related: Array,
});

function formatDate(value) {
    return new Date(value).toLocaleDateString('ro-RO', { day: 'numeric', month: 'long', year: 'numeric' });
}
</script>

<template>
    <SeoHead />

    <PublicLayout>
        <article class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
            <Link :href="route('public.blog.index')" class="text-sm font-semibold text-blue-600 hover:text-blue-500">
                &larr; Inapoi la blog
            </Link>
            <time class="mt-6 block text-sm text-slate-400">{{ formatDate(post.published_at) }}</time>
            <h1 class="mt-2 font-display text-3xl font-bold text-slate-900">{{ post.title }}</h1>
            <div class="prose prose-slate mt-8 max-w-none whitespace-pre-line text-slate-600">
                {{ post.body }}
            </div>
        </article>

        <section v-if="related.length" class="bg-slate-50 py-16">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-xl font-semibold text-slate-900">Articole similare</h2>
                <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <Link
                        v-for="item in related"
                        :key="item.slug"
                        :href="route('public.blog.show', item.slug)"
                        class="block rounded-xl border border-slate-200 bg-white p-5 hover:border-blue-300 hover:shadow-sm"
                    >
                        <h3 class="font-semibold text-slate-900">{{ item.title }}</h3>
                        <p class="mt-2 text-sm text-slate-500">{{ item.excerpt }}</p>
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
