<script setup>
import HorizontalBarList from '@/Components/Charts/HorizontalBarList.vue';
import MonthlyBarChart from '@/Components/Charts/MonthlyBarChart.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    filters: Object,
    summary: Object,
    byMonth: Array,
    byStatus: Object,
    measurements: Array,
});

const from = ref(props.filters.from);
const to = ref(props.filters.to);

const statusLabels = {
    in_progress: 'In lucru',
    completed: 'Finalizata',
};

function money(value) {
    return Number(value).toLocaleString('ro-RO', { minimumFractionDigits: 2 });
}

function number(value) {
    return Number(value).toLocaleString('ro-RO', { maximumFractionDigits: 1 });
}

function applyFilters() {
    router.get(route('admin.reports.measurements'), { from: from.value, to: to.value }, { preserveState: true });
}
</script>

<template>
    <Head title="Raport masuratori" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Raport masuratori</h2>
                <Link :href="route('admin.reports')" class="text-sm font-medium text-blue-600 hover:text-blue-500">Inapoi la rapoarte</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <section class="rounded-lg bg-white p-6 shadow-sm">
                    <form class="flex flex-wrap items-end gap-4" @submit.prevent="applyFilters">
                        <div>
                            <label class="block text-xs font-medium text-slate-500">De la</label>
                            <input v-model="from" type="date" class="mt-1 rounded-md border-slate-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500">Pana la</label>
                            <input v-model="to" type="date" class="mt-1 rounded-md border-slate-300 text-sm" />
                        </div>
                        <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                            Aplica
                        </button>
                    </form>
                </section>

                <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-xs text-slate-500">Masuratori</div>
                        <div class="mt-1 text-xl font-semibold text-slate-900">{{ summary.measurements }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-xs text-slate-500">Oferte generate</div>
                        <div class="mt-1 text-xl font-semibold text-slate-900">{{ summary.offers }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-xs text-slate-500">Suprafata totala</div>
                        <div class="mt-1 text-xl font-semibold text-slate-900">{{ number(summary.totalArea) }} mp</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-xs text-slate-500">Perimetru total</div>
                        <div class="mt-1 text-xl font-semibold text-slate-900">{{ number(summary.totalPerimeter) }} ml</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-xs text-slate-500">Valoare estimata</div>
                        <div class="mt-1 text-xl font-semibold text-slate-900">{{ money(summary.estimatedValue) }} lei</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-xs text-slate-500">Valoare oferte</div>
                        <div class="mt-1 text-xl font-semibold text-green-600">{{ money(summary.offerValue) }} lei</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-xs text-slate-500">Finalizate</div>
                        <div class="mt-1 text-xl font-semibold text-slate-900">{{ summary.completed }} ({{ summary.measurements ? Math.round((summary.completed / summary.measurements) * 100) : 0 }}%)</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-xs text-slate-500">Conversie in oferta</div>
                        <div class="mt-1 text-xl font-semibold text-slate-900">{{ summary.measurements ? Math.round((summary.offers / summary.measurements) * 100) : 0 }}%</div>
                    </div>
                </section>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-500">Masuratori pe luna</h3>
                        <div class="mt-4">
                            <MonthlyBarChart :data="byMonth" unit="masuratori" />
                        </div>
                    </section>

                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-500">Dupa status</h3>
                        <div class="mt-4">
                            <HorizontalBarList :data="byStatus" :labels="statusLabels" />
                        </div>
                    </section>
                </div>

                <section class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-500">Detaliu masuratori ({{ measurements.length }})</h3>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase text-slate-400">
                                    <th class="py-2">Data</th>
                                    <th class="py-2">Masuratoare</th>
                                    <th class="py-2">Client</th>
                                    <th class="py-2">Status</th>
                                    <th class="py-2 text-right">mp</th>
                                    <th class="py-2 text-right">ml</th>
                                    <th class="py-2 text-right">Estimativ</th>
                                    <th class="py-2">Oferta</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="row in measurements" :key="row.id">
                                    <td class="py-2 text-slate-600">{{ row.created_at }}</td>
                                    <td class="py-2 font-medium text-slate-900">{{ row.name }}</td>
                                    <td class="py-2 text-slate-600">
                                        {{ row.client_name }}
                                        <span v-if="row.location" class="text-xs text-slate-400">- {{ row.location }}</span>
                                    </td>
                                    <td class="py-2">
                                        <span
                                            class="inline-block rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="row.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                                        >
                                            {{ statusLabels[row.status] ?? row.status }}
                                        </span>
                                    </td>
                                    <td class="py-2 text-right text-slate-600">{{ number(row.area) }}</td>
                                    <td class="py-2 text-right text-slate-600">{{ number(row.perimeter) }}</td>
                                    <td class="py-2 text-right text-slate-900">{{ money(row.estimate) }} lei</td>
                                    <td class="py-2">
                                        <Link v-if="row.offer" :href="route('sales.offers.show', row.offer.id)" class="text-blue-600 hover:text-blue-500">
                                            Oferta #{{ row.offer.id }} ({{ money(row.offer.total_amount) }} lei)
                                        </Link>
                                        <span v-else class="text-slate-300">-</span>
                                    </td>
                                </tr>
                                <tr v-if="!measurements.length">
                                    <td colspan="8" class="py-4 text-center text-slate-400">Nicio masuratoare in perioada selectata.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>