<script setup>
import Icon from '@/Components/Icon.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    pkg: { type: Object, required: true },
});
</script>

<template>
    <div
        class="relative flex flex-col rounded-2xl border bg-white p-8 shadow-lg transition duration-300 hover:-translate-y-1.5 hover:shadow-2xl"
        :class="pkg.highlight ? 'border-orange-400 ring-2 ring-orange-400' : 'border-slate-200'"
    >
        <div
            v-if="pkg.highlight"
            class="absolute -top-3 left-8 inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-orange-500 to-orange-400 px-3 py-1 text-xs font-semibold text-white shadow-md"
        >
            <Icon name="sparkles" class="h-3.5 w-3.5" />
            Cel mai popular
        </div>
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-orange-400 text-white shadow-md">
            <Icon name="wrench" class="h-5 w-5" />
        </div>
        <h3 class="mt-4 font-display text-lg font-semibold text-slate-900">{{ pkg.name }}</h3>
        <p class="mt-1 text-sm text-slate-500">
            {{ pkg.vizite_an }} {{ pkg.vizite_an === 1 ? 'vizita' : 'vizite' }} pe an &middot;
            raspuns in {{ pkg.timp_raspuns }} &middot; {{ pkg.prioritate }}
        </p>
        <p class="mt-4">
            <span class="font-display text-3xl font-bold text-slate-900">{{ Number(pkg.price_from).toLocaleString('ro-RO') }} lei</span>
            <span class="text-sm text-slate-500"> pe an</span>
        </p>
        <ul class="mt-6 flex-1 space-y-3 text-sm text-slate-600">
            <li v-for="feature in pkg.features" :key="feature" class="flex items-start gap-2">
                <span class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                    <Icon name="check" class="h-3.5 w-3.5" />
                </span>
                {{ feature }}
            </li>
        </ul>
        <Link
            :href="route('public.quote')"
            :data="{ package: pkg.key }"
            class="mt-8 block rounded-md px-4 py-2.5 text-center text-sm font-semibold shadow-sm transition"
            :class="pkg.highlight ? 'bg-orange-500 text-white hover:bg-orange-400' : 'bg-slate-900 text-white hover:bg-slate-700'"
        >
            Vreau pachetul {{ pkg.name }}
        </Link>
    </div>
</template>
