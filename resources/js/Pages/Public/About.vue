<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { computed } from 'vue';
const props = defineProps({ page: Object });
const pageTitle = computed(() => props.page?.title || 'Despre noi');
const pageSubtitle = computed(() => props.page?.subtitle || 'Echipa si principiile de lucru');
const bodyHtml = computed(() => props.page?.content?.body_html || '');
</script>

<template>
    <SeoHead
        :title="pageTitle"
        description="Om Priceput: lucrari de reparatii, montaje si intretinere pentru casa si apartament. Deviz scris inainte de inceperea lucrarii."
    />

    <PublicLayout>
        <section class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
            <h1 class="font-display text-3xl font-bold text-slate-900">{{ pageTitle }}</h1>
            <p class="mt-2 text-slate-500">{{ pageSubtitle }}</p>
            <div v-if="bodyHtml" class="prose mt-6 max-w-none text-slate-600" v-html="bodyHtml"></div>
            <p v-else class="mt-6 text-slate-600">
                Om Priceput este un service pentru casa si apartament: execut personal lucrarile pe care le
                coordonez si implic meserasi din echipa mea atunci cand lucrarea necesita o specializare precisa.
                Pentru fiecare lucrare analizam problema la locul lucrarii, consemnam materialele necesare si
                costul estimat, iar lucrarea incepe doar dupa acceptarea devizului.
            </p>
            <p v-if="!bodyHtml" class="mt-4 text-slate-600">
                Nu practicam preturi estimative fara inspectarea zonei. Devizul se intocmeste dupa ce problema
                a fost constatata, tocmai pentru ca unele reparatii par simple si ascund altele. Recomandam
                inlocuirea unui element atunci cand este cea mai rationala solutie, chiar daca repararea cu
                piese obisnuite ar fi posibila.
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
                    <div class="mt-2 text-sm text-slate-500">Programare intr-o zi lucratoare</div>
                </div>
            </div>

            <h2 class="mt-12 text-xl font-semibold text-slate-900">Cum lucrez</h2>
            <ul class="mt-4 space-y-2 text-slate-600">
                <li>&bull; Pretul se stabileste dupa constatarea la locul lucrarii, nu telefonic.</li>
                <li>&bull; Devizul este scris, cu materialele si manopera prezentate separat.</li>
                <li>&bull; Fara costuri ascunse si fara avansuri inainte de inceperea lucrarii.</li>
                <li>&bull; Daca reparatia nu este rentabila, va informam si nu vom insista pentru executie.</li>
            </ul>
        </section>
    </PublicLayout>
</template>
