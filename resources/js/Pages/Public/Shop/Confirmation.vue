<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({ order: Object });
</script>

<template>
    <SeoHead title="Comanda inregistrata" description="Confirmarea comenzii tale din magazinul online Omul Potrivit." />

    <PublicLayout>
        <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-green-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="mt-6 font-display text-2xl font-bold text-slate-900">Comanda a fost inregistrata!</h1>
            <p class="mt-2 text-slate-500">Numarul comenzii tale: <span class="font-semibold text-slate-800">{{ order.order_number }}</span></p>
            <p class="mt-1 text-slate-500">Te vom contacta in curand la {{ order.phone }} pentru confirmare.</p>

            <div class="mt-8 rounded-xl border border-slate-200 p-6 text-left">
                <h2 class="font-semibold text-slate-900">Produse comandate</h2>
                <ul class="mt-3 divide-y">
                    <li v-for="item in order.items" :key="item.id" class="flex justify-between py-2 text-sm">
                        <span>{{ item.name }} &times; {{ item.quantity }}</span>
                        <span>{{ Number(item.line_total).toFixed(2) }} lei</span>
                    </li>
                </ul>
                <div class="mt-3 flex justify-between border-t pt-3 text-sm text-slate-500">
                    <span>Transport</span>
                    <span>{{ Number(order.shipping_cost) === 0 ? 'Gratuit' : Number(order.shipping_cost).toFixed(2) + ' lei' }}</span>
                </div>
                <div class="mt-1 flex justify-between text-base font-bold text-slate-900">
                    <span>Total</span>
                    <span>{{ Number(order.total).toFixed(2) }} lei</span>
                </div>
            </div>

            <Link :href="route('public.shop.index')" class="mt-8 inline-block text-sm text-blue-600">Continua cumparaturile</Link>
        </section>
    </PublicLayout>
</template>
