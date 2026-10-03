<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { computed } from 'vue';
const props = defineProps({ page: Object });
const pageTitle = computed(() => props.page?.title || 'Despre noi');
const pageSubtitle = computed(() => props.page?.subtitle || 'Echipa si valorile noastre');
const bodyHtml = computed(() => props.page?.content?.body_html || '');
</script>

<template>
    <SeoHead
        :title="pageTitle"
        description="Omul Potrivit: lucrari de reparatii, montaje si intretinere pentru casa si apartament. Deviz scris inainte de lucrare."
    />

    <PublicLayout>
        <section class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
            <h1 class="font-display text-3xl font-bold text-slate-900">{{ pageTitle }}</h1>
            <p class="mt-2 text-slate-500">{{ pageSubtitle }}</p>
            <div v-if="bodyHtml" class="prose mt-6 max-w-none text-slate-600" v-html="bodyHtml"></div>
            <p v-else class="mt-6 text-slate-600">
                Omul Potrivit este un service pentru casa si apartament: lucrez eu la lucrarile mele
                si trimit meserasi din echipa mea atunci cand ai nevoie de o specialitate precisa.
                Pentru fiecare lucrare vedem problema la loc, scriem ce materiale sunt necesare si
                cat costa, si abia dupa acceptarea ta incepem.
            </p>
            <p v-if="!bodyHtml" class="mt-4 text-slate-600">
                Nu preturi din catalog la ghicit. Devizul se face dupa ce am vazut problema, pentru ca
                unele reparatii par usoare si ascund altele. Iti spunem si daca merita sa schimbi
                ceva, chiar daca pot sa-l repar cu bucati obisnuite.
            </p>

            <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 p-6 text-center">
                    <div class="text-3xl font-bold text-blue-600">Gratuit</div>
                    <div class="mt-2 text-sm text-slate-500">Constatarea la locul lucrarii</div>
                </div>
                <div class="rounded-xl border border-slate-200 p-6 text-center">
                    <div class="text-3xl font-bold text-blue-600">24h</div>
                    <div class="mt-2 text-sm text-slate-500">Raspuns la cerere</div>
                </div>
                <div class="rounded-xl border border-slate-200 p-6 text-center">
                    <div class="text-3xl font-bold text-blue-600">1 zi</div>
                    <div class="mt-2 text-sm text-slate-500">Programare la o zi lucratoare</div>
                </div>
            </div>

            <h2 class="mt-12 text-xl font-semibold text-slate-900">Cum lucrez</h2>
            <ul class="mt-4 space-y-2 text-slate-600">
                <li>&bull; Pretul se stabileste dupa ce am vazut lucrarea, nu la telefon.</li>
                <li>&bull; Devizul este scris, cu materialele si manopera separate.</li>
                <li>&bull; Fara costuri ascunse si fara avansuri inainte de incepere.</li>
                <li>&bull; Daca nu merita repara, iti spun si nu te conving sa o faci.</li>
            </ul>
        </section>
    </PublicLayout>
</template>
