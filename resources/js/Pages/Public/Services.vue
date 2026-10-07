<script setup>
import Icon from '@/Components/Icon.vue';
import PackageCard from '@/Components/PackageCard.vue';
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    packages: Array,
    page: Object,
});

const trades = [
    {
        key: 'electric',
        title: 'Electrician',
        text: 'Instalatii si reparatii electrice, tablouri de distributie, prize si corpuri de iluminat, diagnosticarea si eliminarea defectelor.',
        icon: 'bolt',
    },
    {
        key: 'sanitar',
        title: 'Instalator sanitar',
        text: 'Chiuvite, calorifere, tevi infundate, robinete si obiecte sanitare: montaj, schimb si depanare.',
        icon: 'lifebuoy',
    },
    {
        key: 'vopsire',
        title: 'Zugrav',
        text: 'Vopsire, zugraveli, pregatirea suprafetelor si decoratiuni simple, atat in interior, cat si in exterior.',
        icon: 'sparkles',
    },
    {
        key: 'montaj',
        title: 'Montaj',
        text: 'Mobilier la comanda, televizor montat pe perete, rafturi, corpuri suspendate si fixarea corecta a obiectelor.',
        icon: 'wrench',
    },
    {
        key: 'general',
        title: 'Lucrari generale',
        text: 'Reparatii mici de zugraveli, silicon, balamale, usi si orice lucrare care nu se incadreaza in categoriile de mai sus.',
        icon: 'home',
    },
];

const howItWorks = [
    { title: 'Trimiteti cererea', text: 'Alegeti tipul lucrarii, atasati fotografii si descrieti problema in cateva randuri.' },
    { title: 'Va sunam pentru confirmare', text: 'Confirmam ora si venim la constatare, gratuit.' },
    { title: 'Primiti devizul', text: 'Il primiti scris, cu materialele si manopera prezentate separat.' },
    { title: 'Decideti fara graba', text: 'Daca devizul nu va convinge, nu platiti nimic.' },
];
</script>

<template>
    <SeoHead
        title="Ce facem - electrician, instalator, zugrav, montaj"
        description="Lucrari de electrician, instalator, zugrav si montaj pentru casa si apartament. Constatare gratuita si deviz scris inainte de inceperea lucrarii."
    />

    <PublicLayout>
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="font-display text-3xl font-bold text-slate-900">Ce facem</h1>
                <p class="mt-3 text-slate-500">
                    Execut lucrarea eu sau deleg meserasului potrivit. In ambele situatii, problema este analizata
                    la locul lucrarii, iar costul este comunicat inainte de executie.
                </p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="trade in trades" :key="trade.key" class="rounded-xl border border-slate-200 p-6">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-500/10 text-orange-500">
                        <Icon :name="trade.icon" class="h-5 w-5" />
                    </div>
                    <h3 class="mt-4 font-semibold text-slate-900">{{ trade.title }}</h3>
                    <p class="mt-2 text-sm text-slate-500">{{ trade.text }}</p>
                </div>
            </div>

            <div class="mt-12 rounded-2xl bg-brand-navy p-8 text-center text-white">
                <h2 class="font-display text-2xl font-bold">Cum decurge o lucrare</h2>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="(step, index) in howItWorks" :key="step.title" class="text-left">
                        <div class="text-sm font-bold text-orange-400">{{ index + 1 }}</div>
                        <div class="mt-1 font-semibold">{{ step.title }}</div>
                        <p class="mt-1 text-sm text-slate-300">{{ step.text }}</p>
                    </div>
                </div>
                <Link
                    :href="route('public.quote')"
                    class="mt-8 inline-block rounded-md bg-orange-500 px-5 py-3 text-sm font-semibold text-white hover:bg-orange-400"
                >
                    Cereti un deviz
                </Link>
            </div>
        </section>

        <section class="bg-slate-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="font-display text-3xl font-bold text-slate-900">Pachete de intretinere</h2>
                    <p class="mt-3 text-slate-500">
                        Cu un numar fix de vizite si conditii clare, pentru intretinerea recurenta a instalatiilor.
                    </p>
                </div>
                <div class="mt-10 grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <PackageCard v-for="pkg in packages" :key="pkg.key" :pkg="pkg" />
                </div>
                <div class="mt-10 text-center">
                    <Link :href="route('public.quote')" class="text-sm font-semibold text-blue-600 hover:text-blue-500">
                        Aveti o lucrare diferita? Trimiteti cererea &rarr;
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
