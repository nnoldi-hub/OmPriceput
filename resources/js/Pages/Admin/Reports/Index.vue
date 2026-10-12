<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import HorizontalBarList from '@/Components/Charts/HorizontalBarList.vue';
import MonthlyBarChart from '@/Components/Charts/MonthlyBarChart.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    sales: Object,
    technical: Object,
    financial: Object,
});

const offerStatusLabels = {
    draft: 'Draft',
    sent: 'Trimise',
    accepted: 'Acceptate',
    rejected: 'Respinse',
    expired: 'Expirate',
};

const installationStatusLabels = {
    scheduled: 'Programate',
    in_progress: 'In desfasurare',
    completed: 'Finalizate',
    cancelled: 'Anulate',
};

const ticketStatusLabels = {
    open: 'Deschise',
    in_progress: 'In lucru',
    resolved: 'Rezolvate',
    closed: 'Inchise',
};

const ticketPriorityLabels = {
    low: 'Scazuta',
    medium: 'Medie',
    high: 'Ridicata',
};
const expenseCategoryLabels = { material: 'Materiale', transport: 'Transport', manopera: 'Manopera', other: 'Altele' };

function money(value) {
    return Number(value).toLocaleString('ro-RO', { minimumFractionDigits: 2 });
}
</script>

<template>
    <Head title="Rapoarte" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Rapoarte</h2>
                <div class="flex gap-2">
                    <Link :href="route('admin.reports.measurements')" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                        Raport masuratori
                    </Link>
                    <Link :href="route('admin.reports.profit')" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                        Raport profit
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <section class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-500">Venituri incasate (ultimele 6 luni)</h3>
                    <div class="mt-6">
                        <MonthlyBarChart :data="financial.revenueByMonth" />
                    </div>
                </section>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-500">Vanzari</h3>
                        <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-slate-400">Total clienti</dt>
                                <dd class="text-xl font-semibold text-slate-900">{{ sales.totalClients }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Rata conversie</dt>
                                <dd class="text-xl font-semibold text-slate-900">{{ sales.conversionRate }}%</dd>
                            </div>
                        </dl>
                        <h4 class="mt-6 text-xs font-semibold uppercase text-slate-400">Oferte pe status</h4>
                        <div class="mt-3">
                            <HorizontalBarList :data="sales.offersByStatus" :labels="offerStatusLabels" />
                        </div>
                    </section>

                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-500">Tehnic</h3>
                        <h4 class="mt-4 text-xs font-semibold uppercase text-slate-400">Instalari pe status</h4>
                        <div class="mt-3">
                            <HorizontalBarList :data="technical.installationsByStatus" :labels="installationStatusLabels" />
                        </div>
                        <h4 class="mt-6 text-xs font-semibold uppercase text-slate-400">Tichete pe prioritate</h4>
                        <div class="mt-3">
                            <HorizontalBarList :data="technical.ticketsByPriority" :labels="ticketPriorityLabels" />
                        </div>
                    </section>

                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-500">Financiar</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-slate-400">Incasat total</dt>
                                <dd class="font-semibold text-green-600">{{ money(financial.paidTotal) }} lei</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-400">Neincasat</dt>
                                <dd class="font-semibold text-amber-600">{{ money(financial.unpaidTotal) }} lei</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-400">Facturi restante</dt>
                                <dd class="font-semibold" :class="financial.overdueCount > 0 ? 'text-red-600' : 'text-slate-900'">{{ financial.overdueCount }}</dd>
                            </div>
                        </dl>
                        <h4 class="mt-6 text-xs font-semibold uppercase text-slate-400">Tichete pe status</h4>
                        <div class="mt-3">
                            <HorizontalBarList :data="technical.ticketsByStatus" :labels="ticketStatusLabels" />
                        </div>
                        <h4 class="mt-6 text-xs font-semibold uppercase text-slate-400">Cheltuieli pe categorie</h4>
                        <div class="mt-3">
                            <HorizontalBarList :data="financial.expensesByCategory" :labels="expenseCategoryLabels" :value-format="(value) => `${money(value)} lei`" />
                        </div>
                        <h4 class="mt-6 text-xs font-semibold uppercase text-slate-400">Cheltuieli pe furnizor</h4>
                        <div class="mt-3">
                            <HorizontalBarList :data="financial.expensesBySupplier" :value-format="(value) => `${money(value)} lei`" />
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
