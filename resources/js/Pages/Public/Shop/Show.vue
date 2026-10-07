<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useCart } from '@/cart';

const props = defineProps({ product: Object });

const { addItem } = useCart();
const quantity = ref(1);
const added = ref(false);

function addToCart() {
    addItem(props.product, Math.max(1, quantity.value));
    added.value = true;
    setTimeout(() => (added.value = false), 1500);
}
</script>

<template>
    <SeoHead :title="product.name" :description="product.shop_description || product.description || product.name" />

    <PublicLayout>
        <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8">
            <Link :href="route('public.shop.index')" class="text-sm text-blue-600">&larr; Inapoi in magazin</Link>

            <div class="mt-6 grid gap-10 sm:grid-cols-2">
                <div class="flex h-80 items-center justify-center overflow-hidden rounded-xl bg-slate-50">
                    <img v-if="product.image_path" :src="`/storage/${product.image_path}`" :alt="product.name" class="h-full w-full object-contain" />
                    <span v-else class="text-sm text-slate-400">Imagine indisponibila</span>
                </div>

                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-400">{{ product.category_label }}</div>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ product.name }}</h1>

                    <div class="mt-4 flex items-center gap-3">
                        <span class="text-2xl font-bold text-blue-700">{{ product.shop_price.toFixed(2) }} lei</span>
                        <span v-if="product.shop_discount_amount > 0" class="text-lg text-slate-400 line-through">{{ product.unit_price.toFixed(2) }} lei</span>
                    </div>

                    <p class="mt-4 whitespace-pre-line text-slate-600">{{ product.shop_description || product.description || 'Descrierea produsului nu este disponibila.' }}</p>

                    <p v-if="!product.in_stock" class="mt-4 text-sm font-medium text-red-600">Produs indisponibil momentan.</p>
                    <template v-else>
                        <div class="mt-6 flex items-center gap-3">
                            <input v-model.number="quantity" type="number" min="1" :max="product.stock_quantity" class="w-20 rounded-lg border-slate-300 text-sm" />
                            <button class="rounded-lg bg-orange-500 px-6 py-2 text-sm font-semibold text-white hover:bg-orange-600" @click="addToCart">
                                {{ added ? 'Adaugat in cos' : 'Adaugati in cos' }}
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">{{ product.stock_quantity }} bucati in stoc</p>
                    </template>

                    <Link :href="route('public.shop.index')" class="mt-6 inline-block text-sm text-blue-600">Continuati cumparaturile</Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
