<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const { canManage } = usePermissions();

const props = defineProps({
    installation: Object,
    types: Object,
});

const typeLabel = computed(() => props.types?.[props.installation.type] ?? props.installation.type);

const pendingChecklistIndexes = reactive(new Set());

const statusOptions = [
    { value: 'scheduled', label: 'Programata' },
    { value: 'in_progress', label: 'In desfasurare' },
    { value: 'completed', label: 'Finalizata' },
    { value: 'cancelled', label: 'Anulata' },
];

const statusClasses = {
    scheduled: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-amber-100 text-amber-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-700',
};

const isFinalized = computed(() => installationIsFinalized(props.installation));

function installationIsFinalized(installation) {
    return installation.status === 'completed'
        || installation.completed_at
        || installation.handover_at
        || installation.report_number
        || installation.stock_consumed_at;
}

const mapQuery = computed(() => {
    if (props.installation.latitude && props.installation.longitude) {
        return `${props.installation.latitude},${props.installation.longitude}`;
    }
    if (props.installation.address) {
        return props.installation.address;
    }
    return null;
});

const mapSrc = computed(() => mapQuery.value
    ? `https://maps.google.com/maps?q=${encodeURIComponent(mapQuery.value)}&z=15&output=embed`
    : null);

const mapLink = computed(() => mapQuery.value
    ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(mapQuery.value)}`
    : null);

function setStatus(status) {
    if (isFinalized.value && status !== 'completed') return;
    router.patch(route('technical.installations.status', props.installation.id), { status }, { preserveScroll: true });
}

function toggleItem(index) {
    const done = !props.installation.checklist[index].done;
    pendingChecklistIndexes.add(index);
    router.patch(route('technical.installations.checklist', props.installation.id), { index, done }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => pendingChecklistIndexes.delete(index),
    });
}

const checklistProgress = computed(() => {
    if (!props.installation.checklist?.length) return 0;
    const done = props.installation.checklist.filter((i) => i.done).length;
    return Math.round((done / props.installation.checklist.length) * 100);
});

function money(value) {
    return Number(value ?? 0).toLocaleString('ro-RO', { minimumFractionDigits: 2 });
}
</script>

<template>
    <Head :title="`${typeLabel} #${installation.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ typeLabel }} #{{ installation.id }} - {{ installation.client.name }}
                </h2>
                <div class="flex gap-2">
                    <a :href="route('technical.installations.pdf', installation.id)" target="_blank" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Raport PDF
                    </a>
                    <Link v-if="canManage('installations') && !isFinalized" :href="route('technical.installations.edit', installation.id)" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Editeaza
                    </Link>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-500">Costuri si profit</h3>
                    <dl class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                        <div><dt class="text-slate-400">Valoare oferta</dt><dd class="font-semibold text-slate-900">{{ money(installation.cost_report.offer_value) }} lei</dd></div>
                        <div><dt class="text-slate-400">Cost materiale</dt><dd class="text-slate-900">{{ money(installation.cost_report.material_cost) }} lei</dd></div>
                        <div><dt class="text-slate-400">Cost manopera</dt><dd class="text-slate-900">{{ money(installation.cost_report.labor_cost) }} lei</dd></div>
                        <div><dt class="text-slate-400">Cost total</dt><dd class="font-semibold text-slate-900">{{ money(installation.cost_report.total_cost) }} lei</dd></div>
                        <div><dt class="text-slate-400">Cheltuieli reale</dt><dd class="font-semibold text-orange-700">{{ money(installation.cost_report.actual_expenses) }} lei</dd></div>
                        <div><dt class="text-slate-400">Cost real total</dt><dd class="font-semibold text-slate-900">{{ money(installation.cost_report.actual_total_cost) }} lei</dd></div>
                        <div><dt class="text-slate-400">Profit estimat</dt><dd class="font-semibold text-blue-700">{{ money(installation.cost_report.estimated_profit) }} lei</dd></div>
                        <div v-if="installation.cost_report.final_profit !== null"><dt class="text-slate-400">Profit final real</dt><dd class="font-semibold" :class="installation.cost_report.final_profit >= 0 ? 'text-green-700' : 'text-red-700'">{{ money(installation.cost_report.final_profit) }} lei</dd></div>
                    </dl>
                    <div v-if="installation.expenses?.length" class="mt-5 border-t border-slate-100 pt-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Cheltuieli asociate</h4>
                        <div v-for="expense in installation.expenses" :key="expense.id" class="mt-2 flex justify-between text-sm">
                            <span>{{ expense.description }} <span class="text-slate-400">({{ expense.supplier?.name || 'fara furnizor' }})</span></span>
                            <strong>{{ money(expense.amount) }} lei</strong>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-6xl grid-cols-1 gap-6 sm:px-6 lg:grid-cols-3 lg:px-8">
                <div class="space-y-6 lg:col-span-1">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-500">Detalii</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-slate-400">Client</dt>
                                <dd>
                                    <Link :href="route('sales.clients.show', installation.client.id)" class="text-blue-600 hover:text-blue-500">
                                        {{ installation.client.name }}
                                    </Link>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-slate-400">Adresa</dt>
                                <dd class="text-slate-900">{{ installation.address ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Tehnician</dt>
                                <dd class="text-slate-900">{{ installation.technician?.name ?? 'Neasignat' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Data programata</dt>
                                <dd class="text-slate-900">{{ installation.scheduled_at ? new Date(installation.scheduled_at).toLocaleString('ro-RO') : '-' }}</dd>
                            </div>
                            <div v-if="installation.report_number">
                                <dt class="text-slate-400">Numar PV</dt>
                                <dd class="text-slate-900">{{ installation.report_number }}</dd>
                            </div>
                            <div v-if="installation.notes">
                                <dt class="text-slate-400">Note</dt>
                                <dd class="whitespace-pre-line text-slate-900">{{ installation.notes }}</dd>
                            </div>
                        </dl>

                        <h3 class="mt-6 text-sm font-semibold text-slate-500">Status</h3>
                        <span class="mt-2 inline-block rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses[installation.status]">
                            {{ statusOptions.find((s) => s.value === installation.status)?.label }}
                        </span>
                        <div v-if="canManage('installations')" class="mt-3 flex flex-wrap gap-2">
                            <button
                                v-for="option in statusOptions"
                                :key="option.value"
                                :disabled="installation.status === option.value || (isFinalized && option.value !== 'completed')"
                                class="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                @click="setStatus(option.value)"
                            >
                                {{ option.label }}
                            </button>
                        </div>
                    </div>

                    <div v-if="mapSrc" class="overflow-hidden rounded-lg bg-white shadow-sm">
                        <iframe :src="mapSrc" class="h-56 w-full border-0" loading="lazy"></iframe>
                        <a :href="mapLink" target="_blank" class="block p-3 text-center text-sm text-blue-600 hover:text-blue-500">
                            Deschide in Google Maps
                        </a>
                    </div>
                </div>

                <div class="space-y-6 lg:col-span-2">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-500">Checklist instalare</h3>
                            <span class="text-xs text-slate-400">{{ checklistProgress }}% complet</span>
                        </div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full bg-green-500 transition-all" :style="{ width: checklistProgress + '%' }"></div>
                        </div>
                        <ul class="mt-4 space-y-2">
                            <li v-for="(item, index) in installation.checklist" :key="index">
                                <label class="flex items-center gap-3 text-sm">
                                    <input
                                        type="checkbox"
                                        :checked="item.done"
                                        :disabled="!canManage('installations') || pendingChecklistIndexes.has(index)"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 disabled:opacity-50"
                                        @change="toggleItem(index)"
                                    />
                                    <span :class="item.done ? 'text-slate-400 line-through' : 'text-slate-700'">{{ item.label }}</span>
                                </label>
                            </li>
                        </ul>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-500">Executie si receptie</h3>
                        <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                            <div><dt class="text-slate-400">Ore lucrate</dt><dd class="text-slate-900">{{ installation.labor_hours ?? '-' }}</dd></div>
                            <div><dt class="text-slate-400">Client la receptie</dt><dd class="text-slate-900">{{ installation.customer_name ?? '-' }}</dd></div>
                            <div><dt class="text-slate-400">Data receptiei</dt><dd class="text-slate-900">{{ installation.handover_at ? new Date(installation.handover_at).toLocaleString('ro-RO') : '-' }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-slate-400">Materiale consumate</dt><dd class="whitespace-pre-line text-slate-900">{{ installation.materials?.join('\n') || '-' }}</dd></div>
                            <div v-if="installation.material_items?.length" class="sm:col-span-2">
                                <dt class="text-slate-400">Materiale scazute din stoc</dt>
                                <dd class="text-slate-900">
                                    <ul class="mt-1 list-disc pl-5">
                                        <li v-for="item in installation.material_items" :key="`${item.equipment_id}-${item.name}`">
                                            {{ item.name }} — {{ item.quantity }} {{ item.unit }}
                                        </li>
                                    </ul>
                                </dd>
                            </div>
                            <div v-if="installation.service_items?.length" class="sm:col-span-2">
                                <dt class="text-slate-400">Servicii / manopera planificata</dt>
                                <dd class="text-slate-900">
                                    <ul class="mt-1 list-disc pl-5">
                                        <li v-for="item in installation.service_items" :key="`${item.service_id}-${item.name}`">
                                            {{ item.name }} — {{ item.quantity }} {{ item.unit }}
                                        </li>
                                    </ul>
                                </dd>
                            </div>
                            <div class="sm:col-span-2"><dt class="text-slate-400">Observatii client</dt><dd class="whitespace-pre-line text-slate-900">{{ installation.customer_notes || '-' }}</dd></div>
                        </dl>
                        <div v-if="installation.technician_signature || installation.customer_signature" class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div v-if="installation.technician_signature">
                                <p class="text-xs text-slate-400">Semnatura tehnician</p>
                                <img :src="installation.technician_signature" alt="Semnatura tehnician" class="mt-2 h-16 max-w-full object-contain" />
                            </div>
                            <div v-if="installation.customer_signature">
                                <p class="text-xs text-slate-400">Semnatura client</p>
                                <img :src="installation.customer_signature" alt="Semnatura client" class="mt-2 h-16 max-w-full object-contain" />
                            </div>
                        </div>
                        <div v-if="installation.photos?.length" class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <a v-for="photo in installation.photos" :key="photo" :href="photo" target="_blank" rel="noopener">
                                <img :src="photo" alt="Fotografie lucrare" class="h-32 w-full rounded-md object-cover" />
                            </a>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-500">Tichete asociate ({{ installation.tickets.length }})</h3>
                            <Link v-if="canManage('tickets')" :href="route('technical.tickets.create', { client_id: installation.client.id })" class="text-sm text-blue-600 hover:text-blue-500">
                                Deschide tichet nou
                            </Link>
                        </div>
                        <div v-if="installation.tickets.length" class="mt-4 divide-y divide-slate-100">
                            <Link
                                v-for="ticket in installation.tickets"
                                :key="ticket.id"
                                :href="route('technical.tickets.show', ticket.id)"
                                class="block py-3 hover:bg-slate-50"
                            >
                                <div class="font-medium text-slate-900">{{ ticket.subject }}</div>
                                <div class="text-xs text-slate-400">{{ ticket.status }}</div>
                            </Link>
                        </div>
                        <p v-else class="mt-4 text-sm text-slate-400">Niciun tichet deschis pentru aceasta interventie.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
