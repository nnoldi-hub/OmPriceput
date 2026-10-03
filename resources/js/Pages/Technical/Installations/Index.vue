<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const { canManage } = usePermissions();

const props = defineProps({
    installations: Object,
    filters: Object,
    technicians: Array,
    types: Object,
    trades: Object,
});

const form = reactive({
    status: props.filters.status ?? '',
    type: props.filters.type ?? '',
    trade: props.filters.trade ?? '',
    programare: props.filters.programare ?? '',
    technician_id: props.filters.technician_id ?? '',
});

watch(form, () => {
    router.get(route('technical.installations.index'), form, { preserveState: true, replace: true });
});

const statusClasses = {
    scheduled: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-amber-100 text-amber-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-700',
};

const statusLabels = {
    scheduled: 'Programata',
    in_progress: 'In desfasurare',
    completed: 'Finalizata',
    cancelled: 'Anulata',
};
</script>

<template>
    <Head title="Instalari si interventii" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Instalari si interventii</h2>
                <Link v-if="canManage('installations')" :href="route('technical.installations.create')" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                    Programare noua
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-5">
                    <select v-model="form.status" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Toate statusurile</option>
                        <option value="scheduled">Programata</option>
                        <option value="in_progress">In desfasurare</option>
                        <option value="completed">Finalizata</option>
                        <option value="cancelled">Anulata</option>
                    </select>
                    <select v-model="form.type" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Toate tipurile</option>
                        <option v-for="(label, value) in types" :key="value" :value="value">{{ label }}</option>
                    </select>
                    <select v-model="form.trade" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Toate meseriile</option>
                        <option v-for="(label, value) in trades" :key="value" :value="value">{{ label }}</option>
                    </select>
                    <select v-model="form.programare" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Programare si constatare</option>
                        <option value="programate">Cu data si ora</option>
                        <option value="neprogramate">De programat</option>
                    </select>
                    <select v-model="form.technician_id" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Toti meserasii</option>
                        <option v-for="tech in technicians" :key="tech.id" :value="tech.id">{{ tech.name }}</option>
                    </select>
                </div>

                <div class="rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Client</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Tip</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Data</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Meseras</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="installation in installations.data" :key="installation.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <Link :href="route('technical.installations.show', installation.id)" class="font-medium text-blue-600 hover:text-blue-500">
                                        {{ installation.client.name }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    {{ types[installation.type] ?? installation.type }}
                                    <span v-if="installation.requested_type" class="block text-xs text-slate-400">
                                        cerut: {{ types[installation.requested_type] ?? installation.requested_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    {{ installation.scheduled_at ? new Date(installation.scheduled_at).toLocaleString('ro-RO') : 'De programat' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ installation.technician?.name ?? 'De repartizat' }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses[installation.status]">
                                        {{ statusLabels[installation.status] }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!installations.data.length">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">Nicio programare gasita.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                </div>

                <div v-if="installations.links.length > 3" class="mt-4 flex flex-wrap gap-2">
                    <Link
                        v-for="link in installations.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        :class="[
                            'rounded-md px-3 py-1.5 text-sm',
                            link.active ? 'bg-blue-600 text-white' : 'bg-white text-slate-500 hover:bg-slate-100',
                            !link.url ? 'pointer-events-none opacity-40' : '',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
