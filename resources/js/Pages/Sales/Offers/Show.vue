<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    offer: Object,
    profitability: Object,
});

const sectionDefs = [
    { key: 'materials', title: 'A. Materiale necesare', priced: true },
    { key: 'labor', title: 'B. Manopera', priced: true },
    { key: 'client_materials', title: 'C. Materiale achizitionate de client', priced: false },
];

const groups = computed(() => {
    const result = { materials: [], labor: [], client_materials: [] };

    for (const item of props.offer.items) {
        const section = item.section || (item.equipment_id ? 'materials' : 'labor');
        (result[section] ??= []).push(item);
    }

    return result;
});

const subtotals = computed(() => {
    const sum = (items) => items.reduce((total, item) => total + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0), 0);

    return {
        materials: sum(groups.value.materials),
        labor: sum(groups.value.labor),
    };
});

const grandTotal = computed(() => subtotals.value.materials + subtotals.value.labor);

const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'sent', label: 'Trimisa' },
    { value: 'accepted', label: 'Acceptata' },
    { value: 'rejected', label: 'Respinsa' },
    { value: 'expired', label: 'Expirata' },
];

const statusClasses = {
    draft: 'bg-slate-100 text-slate-600',
    sent: 'bg-blue-100 text-blue-700',
    accepted: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-700',
    expired: 'bg-slate-100 text-slate-500',
};

function money(value) {
    return Number(value).toLocaleString('ro-RO', { minimumFractionDigits: 2 });
}

function setStatus(status) {
    router.patch(route('sales.offers.status', props.offer.id), { status }, { preserveScroll: true });
}

function sendOffer() {
    const action = props.offer.status === 'sent' ? 'Retrimite oferta' : 'Trimite oferta';
    if (confirm(`${action}? Clientul va primi o noua notificare.`)) {
        setStatus('sent');
    }
}

function destroy() {
    if (confirm('Stergi aceasta oferta?')) {
        router.delete(route('sales.offers.destroy', props.offer.id));
    }
}
</script>

<template>
    <Head :title="`Oferta #${offer.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Oferta #{{ offer.id }} - {{ offer.title }}</h2>
                <div class="flex gap-2">
                    <a :href="route('sales.offers.pdf', offer.id)" target="_blank" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Descarca PDF
                    </a>
                    <Link :href="route('sales.offers.edit', offer.id)" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Editeaza
                    </Link>
                    <button class="rounded-md border border-red-300 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50" @click="destroy">
                        Sterge
                    </button>
                    <button v-if="['draft', 'sent'].includes(offer.status)" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500" @click="sendOffer">
                        {{ offer.status === 'sent' ? 'Retrimite oferta' : 'Trimite oferta' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-5xl grid-cols-1 gap-6 sm:px-6 lg:grid-cols-3 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm lg:col-span-1">
                    <h3 class="text-sm font-semibold text-slate-500">Client</h3>
                    <Link :href="route('sales.clients.show', offer.client.id)" class="mt-2 block font-medium text-blue-600 hover:text-blue-500">
                        {{ offer.client.name }}
                    </Link>
                    <p class="text-sm text-slate-500">{{ offer.client.phone }}</p>
                    <p class="text-sm text-slate-500">{{ offer.client.email }}</p>

                    <h3 class="mt-6 text-sm font-semibold text-slate-500">Status</h3>
                    <span class="mt-2 inline-block rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses[offer.status]">
                        {{ offer.status }}
                    </span>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-for="option in statusOptions"
                            :key="option.value"
                            :disabled="offer.status === option.value"
                            class="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                            @click="setStatus(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>

                    <div class="mt-6 text-sm text-slate-500">
                        <p>Creata de: {{ offer.user?.name }}</p>
                        <p>Creata la: {{ new Date(offer.created_at).toLocaleDateString('ro-RO') }}</p>
                        <p v-if="offer.valid_until">Valabila pana la: {{ new Date(offer.valid_until).toLocaleDateString('ro-RO') }}</p>
                    </div>
                </div>

                <div class="space-y-6 lg:col-span-2">
                    <div class="rounded-lg p-5 shadow-sm" :class="profitability.margin_percent < profitability.minimum_margin_percent ? 'border border-red-200 bg-red-50' : 'border border-emerald-200 bg-emerald-50'">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold" :class="profitability.margin_percent < profitability.minimum_margin_percent ? 'text-red-800' : 'text-emerald-800'">Profitabilitate estimata</h3>
                            <span class="text-lg font-bold" :class="profitability.margin_percent < profitability.minimum_margin_percent ? 'text-red-700' : 'text-emerald-700'">{{ money(profitability.margin_percent) }}% marja</span>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-3 text-sm">
                            <div><div class="text-slate-500">Cost estimat</div><strong>{{ money(profitability.estimated_cost) }} lei</strong></div>
                            <div><div class="text-slate-500">Profit estimat</div><strong>{{ money(profitability.estimated_profit) }} lei</strong></div>
                            <div><div class="text-slate-500">Prag minim</div><strong>{{ money(profitability.minimum_margin_percent) }}%</strong></div>
                        </div>
                        <p v-if="profitability.uncosted_items" class="mt-3 text-sm text-amber-800">Atentie: {{ profitability.uncosted_items }} articol(e) nu au cost configurat.</p>
                        <p v-if="profitability.margin_percent < profitability.minimum_margin_percent" class="mt-2 text-sm font-medium text-red-700">Oferta este sub marja minima configurata. Verifica pretul inainte de acceptare.</p>
                    </div>
                    <div v-for="section in sectionDefs" :key="section.key" class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-500">{{ section.title }}</h3>
                            <span v-if="section.priced" class="text-sm text-slate-500">Subtotal: <strong class="text-slate-900">{{ money(subtotals[section.key]) }} lei</strong></span>
                        </div>
                        <table class="mt-4 min-w-full divide-y divide-slate-200">
                            <thead>
                                <tr class="text-left text-xs font-medium uppercase text-slate-500">
                                    <th class="py-2">Descriere</th>
                                    <th class="py-2 text-right">Cant.</th>
                                    <th class="py-2 text-right">UM</th>
                                    <th v-if="section.priced" class="py-2 text-right">Pret unitar</th>
                                    <th v-if="section.priced" class="py-2 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in groups[section.key]" :key="item.id">
                                    <td class="py-2">{{ item.description }}</td>
                                    <td class="py-2 text-right">{{ item.quantity }}</td>
                                    <td class="py-2 text-right">{{ item.unit }}</td>
                                    <td v-if="section.priced" class="py-2 text-right">{{ money(item.unit_price) }} lei</td>
                                    <td v-if="section.priced" class="py-2 text-right">{{ money(item.quantity * item.unit_price) }} lei</td>
                                </tr>
                                <tr v-if="!groups[section.key].length">
                                    <td :colspan="section.priced ? 5 : 3" class="py-2 text-sm text-slate-400">Nicio linie.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="rounded-lg bg-slate-900 p-6 shadow-sm">
                        <div class="flex items-center justify-between text-white">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-400">Materiale: {{ money(subtotals.materials) }} lei</p>
                                <p class="text-xs uppercase tracking-wide text-slate-400">Manopera: {{ money(subtotals.labor) }} lei</p>
                            </div>
                            <div class="text-right">
                                <span class="text-sm text-slate-400">Total (A + B)</span>
                                <p class="text-2xl font-bold">{{ money(grandTotal) }} lei</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="offer.notes" class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-500">Note interne</h3>
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ offer.notes }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
