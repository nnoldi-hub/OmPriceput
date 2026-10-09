<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useCart } from '@/cart';

const props = defineProps({
    products: Object,
    filters: Object,
    categories: Object,
});

const { addItem } = useCart();
const search = ref(props.filters?.search ?? '');
const category = ref(props.filters?.category ?? '');
const addedId = ref(null);

function applyFilters() {
    router.get(route('public.shop.index'), { search: search.value || undefined, category: category.value || undefined }, {
        preserveState: true,
        replace: true,
    });
}

function addToCart(product) {
    addItem(product, 1);
    addedId.value = product.id;
    setTimeout(() => (addedId.value = null), 1500);
}
</script>

<template>
    <SeoHead />

    <PublicLayout>
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="font-display text-3xl font-bold text-slate-900">Magazin online</h1>
                <p class="mt-3 text-slate-500">Materiale, consumabile si piese de schimb, disponibile pentru comanda online.</p>
            </div>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cautati produse..."
                    class="w-full max-w-xs rounded-lg border-slate-300 text-sm"
                    @keyup.enter="applyFilters"
                />
                <select v-model="category" class="rounded-lg border-slate-300 text-sm" @change="applyFilters">
                    <option value="">Toate categoriile</option>
                    <option v-for="(label, value) in categories" :key="value" :value="value">{{ label }}</option>
                </select>
                <button class="inline-flex min-h-[48px] items-center justify-center rounded-lg bg-brand-navy px-4 py-2 text-sm font-medium text-white" @click="applyFilters">Filtrati</button>
            </div>

            <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="product in products.data" :key="product.id" class="flex flex-col rounded-xl border border-slate-200 p-5 hover:border-blue-300 hover:shadow-sm">
                    <Link :href="route('public.shop.show', product.slug)" class="block">
                        <div class="flex h-40 items-center justify-center overflow-hidden rounded-lg bg-slate-50">
                            <img v-if="product.image_path" :src="`/storage/${product.image_path}`" :alt="product.name" class="h-full w-full object-contain" />
                            <span v-else class="text-sm text-slate-400">Imagine indisponibila</span>
                        </div>
                        <div class="mt-3 text-xs uppercase tracking-wide text-slate-400">{{ product.category_label }}</div>
                        <h2 class="mt-1 font-semibold text-slate-900">{{ product.name }}</h2>
                    </Link>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="text-lg font-bold text-blue-700">{{ product.shop_price.toFixed(2) }} lei</span>
                        <span v-if="product.shop_discount_amount > 0" class="text-sm text-slate-400 line-through">{{ product.unit_price.toFixed(2) }} lei</span>
                    </div>
                    <p v-if="!product.in_stock" class="mt-1 text-xs font-medium text-red-600">Stoc epuizat</p>
                    <button
                        class="mt-4 inline-flex min-h-[48px] items-center justify-center rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!product.in_stock"
                        @click="addToCart(product)"
                    >
                        {{ addedId === product.id ? 'Adaugat in cos' : 'Adaugati in cos' }}
                    </button>
                </div>
            </div>

            <p v-if="!products.data.length" class="mt-10 text-center text-slate-500">Nu exista produse disponibile in acest moment.</p>

            <div v-if="products.links.length > 3" class="mt-10 flex flex-wrap justify-center gap-2">
                <Link
                    v-for="link in products.links"
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
