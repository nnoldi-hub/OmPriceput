<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    reviews: Object,
    services: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ count: 0, average: 0, distribution: {} }) },
    filters: { type: Object, default: () => ({ serviciu: '' }) },
});

function stars(rating) {
    const value = Math.round(Number(rating) || 0);
    return '★★★★★'.slice(0, value) + '☆☆☆☆☆'.slice(0, 5 - value);
}

function filterBy(service) {
    router.get(
        route('public.reviews.index', service ? { serviciu: service } : {}),
        {},
        { preserveScroll: true, preserveState: true, replace: true },
    );
}

function barPercent(star) {
    if (!props.stats.count) {
        return 0;
    }

    return Math.round(((props.stats.distribution?.[star] ?? 0) / props.stats.count) * 100);
}
</script>

<template>
    <SeoHead />

    <PublicLayout>
        <section class="border-b border-slate-100 bg-slate-50 py-14">
            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <h1 class="font-display text-3xl font-bold text-slate-900 sm:text-4xl">Recenzii clienți</h1>
                <p class="mx-auto mt-3 max-w-2xl text-slate-500">
                    Recenzii reale de la clienții care au lucrat cu noi. Fiecare părere este publicată doar cu acordul clientului.
                </p>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div v-if="stats.count" class="mb-12 grid grid-cols-1 gap-10 rounded-2xl border border-slate-200 p-8 lg:grid-cols-[auto,1fr] lg:items-center">
                <div class="text-center lg:pr-10">
                    <div class="font-display text-5xl font-bold text-slate-900">{{ stats.average }}</div>
                    <div class="mt-1 text-lg text-amber-500">{{ stars(Math.round(stats.average)) }}</div>
                    <div class="mt-1 text-sm text-slate-500">{{ stats.count }} recenzii</div>
                </div>
                <div class="space-y-2">
                    <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="flex items-center gap-3 text-sm">
                        <span class="w-8 shrink-0 text-slate-500">{{ star }}★</span>
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-amber-400" :style="{ width: barPercent(star) + '%' }" />
                        </div>
                        <span class="w-10 shrink-0 text-right text-slate-400">{{ stats.distribution?.[star] ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <div v-if="services.length" class="mb-8 flex flex-wrap gap-2">
                <button
                    type="button"
                    :class="[
                        'inline-flex min-h-[44px] items-center rounded-full border px-4 text-sm font-medium transition',
                        !filters.serviciu ? 'border-orange-500 bg-orange-500 text-white' : 'border-slate-200 text-slate-600 hover:border-slate-300',
                    ]"
                    @click="filterBy(null)"
                >
                    Toate
                </button>
                <button
                    v-for="service in services"
                    :key="service"
                    type="button"
                    :class="[
                        'inline-flex min-h-[44px] items-center rounded-full border px-4 text-sm font-medium transition',
                        filters.serviciu === service ? 'border-orange-500 bg-orange-500 text-white' : 'border-slate-200 text-slate-600 hover:border-slate-300',
                    ]"
                    @click="filterBy(service)"
                >
                    {{ service }}
                </button>
            </div>

            <div v-if="reviews.data.length" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <figure
                    v-for="(review, index) in reviews.data"
                    :key="index"
                    class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <div class="text-lg text-amber-500">{{ stars(review.rating) }}</div>
                    <blockquote class="mt-3 flex-1 text-sm text-slate-600">{{ review.comment }}</blockquote>
                    <figcaption class="mt-4 text-sm font-semibold text-slate-900">
                        {{ review.name }}
                        <span v-if="review.service || review.city" class="font-normal text-slate-400">
                            · {{ [review.service, review.city].filter(Boolean).join(', ') }}
                        </span>
                    </figcaption>
                </figure>
            </div>
            <div v-else class="rounded-2xl border border-dashed border-slate-300 py-16 text-center text-slate-500">
                Nu există recenzii pentru acest filtru încă.
            </div>

            <div v-if="reviews.links.length > 3" class="mt-12 flex flex-wrap justify-center gap-2">
                <Link
                    v-for="link in reviews.links"
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

            <div class="mt-14 rounded-2xl bg-brand-navy p-8 text-center text-white">
                <h2 class="font-display text-2xl font-bold">Vrei și tu o lucrare făcută ca la carte?</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm text-slate-300">
                    Trimite cererea și primești devizul scris după constatarea gratuită.
                </p>
                <Link
                    :href="route('public.quote')"
                    class="mt-6 inline-block rounded-md bg-orange-500 px-5 py-3 text-sm font-semibold text-white hover:bg-orange-400"
                >
                    Cereti un deviz
                </Link>
            </div>
        </section>
    </PublicLayout>
</template>
