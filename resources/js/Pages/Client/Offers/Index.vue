<script setup>
import ClientLayout from '@/Layouts/ClientLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ offers: Array });

const sectionDefs = [
    { key: 'materials', title: 'A. Materiale necesare', priced: true },
    { key: 'labor', title: 'B. Manopera', priced: true },
    { key: 'client_materials', title: 'C. Materiale achizitionate de client', priced: false },
];

function sectionOf(item) {
    return item.section || (item.equipment_id ? 'materials' : 'labor');
}

function itemsFor(offer, section) {
    return offer.items.filter((item) => sectionOf(item) === section);
}

function subtotal(offer, section) {
    if (section === 'client_materials') return 0;
    return itemsFor(offer, section).reduce((total, item) => total + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0), 0);
}

function grandTotal(offer) {
    return subtotal(offer, 'materials') + subtotal(offer, 'labor');
}

function money(value) {
    return Number(value).toLocaleString('ro-RO', { minimumFractionDigits: 2 });
}

function respond(offer, status) {
    const message = window.prompt(status === 'accepted'
        ? 'Mesaj optional pentru echipa:'
        : 'Spune-ne, te rog, de ce respingi oferta (optional):', '');
    if (message === null) return;
    useForm({ status, message }).patch(route('client.offers.status', offer.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Oferte" />
    <ClientLayout title="Oferte">
        <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-slate-900">Ofertele mele</h1>
            <div v-for="offer in offers" :key="offer.id" class="rounded-xl bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ offer.title }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Oferta #{{ offer.id }}</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">{{ offer.status }}</span>
                </div>

                <div v-for="section in sectionDefs" :key="section.key" class="mt-5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ section.title }}</h3>
                        <span v-if="section.priced" class="text-xs text-slate-500">Subtotal: {{ money(subtotal(offer, section.key)) }} lei</span>
                    </div>
                    <div class="mt-2 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-b text-left text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="py-2">Descriere</th>
                                    <th class="py-2 text-right">Cant.</th>
                                    <th class="py-2 text-right">UM</th>
                                    <th v-if="section.priced" class="py-2 text-right">Pret</th>
                                    <th v-if="section.priced" class="py-2 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="item in itemsFor(offer, section.key)" :key="item.id">
                                    <td class="py-2">{{ item.description }}</td>
                                    <td class="py-2 text-right">{{ item.quantity }}</td>
                                    <td class="py-2 text-right">{{ item.unit }}</td>
                                    <td v-if="section.priced" class="py-2 text-right">{{ money(item.unit_price) }} lei</td>
                                    <td v-if="section.priced" class="py-2 text-right">{{ money(item.quantity * item.unit_price) }} lei</td>
                                </tr>
                                <tr v-if="!itemsFor(offer, section.key).length">
                                    <td :colspan="section.priced ? 5 : 3" class="py-2 text-slate-400">Nicio linie.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <p class="mt-4 text-right text-lg font-bold text-slate-900">Total: {{ money(grandTotal(offer)) }} lei</p>
                <div v-if="offer.status === 'sent'" class="mt-5 flex flex-wrap justify-end gap-3 border-t pt-4">
                    <button class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white" @click="respond(offer, 'accepted')">Accepta oferta</button>
                    <button class="rounded-md border border-red-300 px-4 py-2 text-sm font-semibold text-red-700" @click="respond(offer, 'rejected')">Respinge oferta</button>
                </div>
            </div>
            <p v-if="!offers.length" class="rounded-xl bg-white p-6 text-slate-500">Nu exista oferte disponibile.</p>
        </div>
    </ClientLayout>
</template>
