<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const { canManage } = usePermissions();

const props = defineProps({
    installation: Object,
    types: Object,
    review: { type: Object, default: null },
    toolboxes: { type: Array, default: () => [] },
});

const typeLabel = computed(() => props.types?.[props.installation.type] ?? props.installation.type);

const pendingChecklistIndexes = reactive(new Set());

const statusOptions = [
    { value: 'scheduled', label: 'Programată' },
    { value: 'in_progress', label: 'În desfășurare' },
    { value: 'completed', label: 'Finalizată' },
    { value: 'cancelled', label: 'Anulată' },
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
    if (status === 'completed' && !isFinalized.value && hasMaterials.value) {
        openCompletion();
        return;
    }
    router.patch(route('technical.installations.status', props.installation.id), { status }, { preserveScroll: true });
}

// ---- Cerere de recenzie ----
const reviewStatusLabels = {
    pending: 'Cerere trimisă',
    submitted: 'Răspuns primit (de moderat)',
    approved: 'Publicată pe site',
    rejected: 'Respinsă',
};

const reviewStatus = computed(() => {
    if (!props.review) return null;
    return reviewStatusLabels[props.review.status] ?? props.review.status;
});

function requestReview() {
    const message = props.review?.status === 'pending'
        ? 'Retrimiți linkul de recenzie prin SMS clientului?'
        : 'Trimite clientului linkul pentru a lăsa o recenzie?';
    if (confirm(message)) {
        router.post(route('technical.installations.review', props.installation.id), {}, { preserveScroll: true });
    }
}

function stars(rating) {
    const value = Number(rating) || 0;
    return '★'.repeat(value) + '☆'.repeat(Math.max(0, 5 - value));
}

// ---- Finalizare cu consum real de materiale ----
const hasMaterials = computed(() => (props.installation.material_items ?? []).length > 0);
const showCompletion = ref(false);
const completionRows = reactive([]);
const completionError = computed(() =>
    completionRows.some((row) => (Number(row.consumed) || 0) + (Number(row.left_with_client) || 0) > (Number(row.planned) || 0)),
);

function openCompletion() {
    completionRows.splice(
        0,
        completionRows.length,
        ...(props.installation.material_items ?? []).map((item) => ({
            equipment_id: item.equipment_id ?? null,
            name: item.name,
            unit: item.unit ?? 'buc',
            planned: Number(item.quantity) || 0,
            consumed: Number(item.quantity) || 0,
            left_with_client: 0,
        })),
    );
    showCompletion.value = true;
}

function returned(row) {
    return Math.max(0, (Number(row.planned) || 0) - (Number(row.consumed) || 0) - (Number(row.left_with_client) || 0));
}

function submitCompletion() {
    if (completionError.value) return;
    router.patch(route('technical.installations.status', props.installation.id), {
        status: 'completed',
        consumption: completionRows.map((row) => ({ ...row })),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showCompletion.value = false;
        },
    });
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

// ---- Cutii de luat (bifele sunt doar pentru încărcare, nu se salvează) ----
const openBoxes = ref([]);
const packed = reactive({});

function toggleBox(id) {
    const i = openBoxes.value.indexOf(id);
    if (i === -1) openBoxes.value.push(id);
    else openBoxes.value.splice(i, 1);
}

function packedCount(box) {
    return box.contents.filter((_, i) => packed[`${box.id}-${i}`]).length;
}

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
                    <button v-if="canManage('installations')" type="button" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="requestReview">
                        Cere părere
                    </button>
                    <a :href="route('technical.installations.pdf', installation.id)" target="_blank" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Raport PDF
                    </a>
                    <Link v-if="canManage('installations') && !isFinalized" :href="route('technical.installations.edit', installation.id)" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Editează
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-6xl grid-cols-1 gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
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
                            <div v-if="installation.client.phone">
                                <dt class="text-slate-400">Telefon</dt>
                                <dd><a :href="`tel:${installation.client.phone}`" class="text-blue-600 hover:text-blue-500">{{ installation.client.phone }}</a></dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Adresă</dt>
                                <dd class="text-slate-900">{{ installation.address ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Tehnician</dt>
                                <dd class="text-slate-900">{{ installation.technician?.name ?? 'Neasignat' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Data programată</dt>
                                <dd class="text-slate-900">{{ installation.scheduled_at ? new Date(installation.scheduled_at).toLocaleString('ro-RO') : '-' }}</dd>
                            </div>
                            <div v-if="installation.report_number">
                                <dt class="text-slate-400">Număr PV</dt>
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

                        <div v-if="review" class="mt-5 border-t border-slate-100 pt-4">
                            <h3 class="text-sm font-semibold text-slate-500">Recenzie client</h3>
                            <p class="mt-2 text-sm text-slate-700">{{ reviewStatus }}</p>
                            <p v-if="review.rating" class="mt-1 text-lg text-amber-500">{{ stars(review.rating) }}</p>
                            <p v-if="review.comment" class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ review.comment }}</p>
                        </div>
                    </div>

                    <div v-if="toolboxes.length" class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-500">Cutii de luat</h3>
                        <p class="mt-1 text-xs text-slate-400">Bifează pe măsură ce încarci. Bifele nu se salvează.</p>

                        <div v-for="box in toolboxes" :key="box.id" class="mt-3 rounded-md border border-slate-200">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between px-3 py-2 text-left"
                                :aria-expanded="openBoxes.includes(box.id)"
                                @click="toggleBox(box.id)"
                            >
                                <span class="text-sm font-semibold text-slate-800">{{ box.name }}</span>
                                <span class="flex items-center gap-2 text-xs text-slate-500">
                                    {{ packedCount(box) }}/{{ box.contents.length }}
                                    <span class="text-lg leading-none text-slate-400">{{ openBoxes.includes(box.id) ? '−' : '+' }}</span>
                                </span>
                            </button>
                            <ul v-show="openBoxes.includes(box.id)" class="space-y-1.5 border-t border-slate-100 px-3 py-2">
                                <li v-for="(line, i) in box.contents" :key="i">
                                    <label class="flex items-start gap-2 text-sm">
                                        <input v-model="packed[`${box.id}-${i}`]" type="checkbox" class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                        <span :class="packed[`${box.id}-${i}`] ? 'text-slate-400 line-through' : 'text-slate-700'">{{ line }}</span>
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div v-if="mapSrc" class="overflow-hidden rounded-lg bg-white shadow-sm">
                        <iframe :src="mapSrc" class="h-56 w-full border-0" loading="lazy"></iframe>
                        <a :href="mapLink" target="_blank" class="block p-3 text-center text-sm text-blue-600 hover:text-blue-500">
                            Deschide în Google Maps
                        </a>
                    </div>
                </div>

                <div class="space-y-6 lg:col-span-2">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-500">Costuri și profit</h3>
                        <dl class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                            <div><dt class="text-slate-400">Valoare ofertă</dt><dd class="font-semibold text-slate-900">{{ money(installation.cost_report.offer_value) }} lei</dd></div>
                            <div><dt class="text-slate-400">Cost materiale</dt><dd class="text-slate-900">{{ money(installation.cost_report.material_cost) }} lei</dd></div>
                            <div><dt class="text-slate-400">Cost manoperă</dt><dd class="text-slate-900">{{ money(installation.cost_report.labor_cost) }} lei</dd></div>
                            <div><dt class="text-slate-400">Cost total</dt><dd class="font-semibold text-slate-900">{{ money(installation.cost_report.total_cost) }} lei</dd></div>
                            <div><dt class="text-slate-400">Cheltuieli reale</dt><dd class="font-semibold text-orange-700">{{ money(installation.cost_report.actual_expenses) }} lei</dd></div>
                            <div><dt class="text-slate-400">Cost real total</dt><dd class="font-semibold text-slate-900">{{ money(installation.cost_report.actual_total_cost) }} lei</dd></div>
                            <div><dt class="text-slate-400">Profit estimat</dt><dd class="font-semibold text-blue-700">{{ money(installation.cost_report.estimated_profit) }} lei</dd></div>
                            <div v-if="installation.cost_report.final_profit !== null"><dt class="text-slate-400">Profit final real</dt><dd class="font-semibold" :class="installation.cost_report.final_profit >= 0 ? 'text-green-700' : 'text-red-700'">{{ money(installation.cost_report.final_profit) }} lei</dd></div>
                        </dl>
                        <div v-if="installation.expenses?.length" class="mt-5 border-t border-slate-100 pt-4">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Cheltuieli asociate</h4>
                            <div v-for="expense in installation.expenses" :key="expense.id" class="mt-2 flex justify-between text-sm">
                                <span>{{ expense.description }} <span class="text-slate-400">({{ expense.supplier?.name || 'fără furnizor' }})</span></span>
                                <strong>{{ money(expense.amount) }} lei</strong>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-500">Checklist</h3>
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
                        <h3 class="text-sm font-semibold text-slate-500">Execuție și recepție</h3>
                        <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                            <div><dt class="text-slate-400">Ore lucrate</dt><dd class="text-slate-900">{{ installation.labor_hours ?? '-' }}</dd></div>
                            <div><dt class="text-slate-400">Client la recepție</dt><dd class="text-slate-900">{{ installation.customer_name ?? '-' }}</dd></div>
                            <div><dt class="text-slate-400">Data recepției</dt><dd class="text-slate-900">{{ installation.handover_at ? new Date(installation.handover_at).toLocaleString('ro-RO') : '-' }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-slate-400">Materiale consumate</dt><dd class="whitespace-pre-line text-slate-900">{{ installation.materials?.join('\n') || '-' }}</dd></div>
                            <div v-if="installation.material_items?.length" class="sm:col-span-2">
                                <dt class="text-slate-400">Materiale scăzute din stoc</dt>
                                <dd class="text-slate-900">
                                    <ul class="mt-1 list-disc pl-5">
                                        <li v-for="item in installation.material_items" :key="`${item.equipment_id}-${item.name}`">
                                            {{ item.name }} — {{ item.quantity }} {{ item.unit }}
                                        </li>
                                    </ul>
                                </dd>
                            </div>
                            <div v-if="installation.consumption?.length" class="sm:col-span-2">
                                <dt class="text-slate-400">Consum și retur materiale</dt>
                                <dd class="text-slate-900">
                                    <ul class="mt-1 list-disc pl-5">
                                        <li v-for="(line, index) in installation.consumption" :key="`${line.equipment_id}-${index}`">
                                            {{ line.name }} — consumat {{ line.consumed }} {{ line.unit }}<span v-if="line.left_with_client">, rămas la client {{ line.left_with_client }}</span><span v-if="line.returned">, returnat în stoc {{ line.returned }}</span>
                                        </li>
                                    </ul>
                                </dd>
                            </div>
                            <div v-if="installation.service_items?.length" class="sm:col-span-2">
                                <dt class="text-slate-400">Servicii / manoperă planificată</dt>
                                <dd class="text-slate-900">
                                    <ul class="mt-1 list-disc pl-5">
                                        <li v-for="item in installation.service_items" :key="`${item.service_id}-${item.name}`">
                                            {{ item.name }} — {{ item.quantity }} {{ item.unit }}
                                        </li>
                                    </ul>
                                </dd>
                            </div>
                            <div class="sm:col-span-2"><dt class="text-slate-400">Observații client</dt><dd class="whitespace-pre-line text-slate-900">{{ installation.customer_notes || '-' }}</dd></div>
                        </dl>
                        <div v-if="installation.technician_signature || installation.customer_signature" class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div v-if="installation.technician_signature">
                                <p class="text-xs text-slate-400">Semnătură tehnician</p>
                                <img :src="installation.technician_signature" alt="Semnătură tehnician" class="mt-2 h-16 max-w-full object-contain" />
                            </div>
                            <div v-if="installation.customer_signature">
                                <p class="text-xs text-slate-400">Semnătură client</p>
                                <img :src="installation.customer_signature" alt="Semnătură client" class="mt-2 h-16 max-w-full object-contain" />
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
                        <p v-else class="mt-4 text-sm text-slate-400">Niciun tichet deschis pentru această intervenție.</p>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showCompletion" max-width="2xl" @close="showCompletion = false">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-slate-800">Finalizează lucrarea</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Completează cât s-a consumat efectiv și cât a rămas la client. Restul se întoarce în stoc.
                </p>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-medium uppercase text-slate-500">
                                <th class="py-2 pr-2">Material</th>
                                <th class="py-2 px-2 text-right">Planificat</th>
                                <th class="py-2 px-2 text-right">Consumat</th>
                                <th class="py-2 px-2 text-right">Rămas la client</th>
                                <th class="py-2 pl-2 text-right">Returnat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(row, index) in completionRows" :key="index">
                                <td class="py-2 pr-2">
                                    <div class="font-medium text-slate-800">{{ row.name }}</div>
                                    <div class="text-xs text-slate-400">{{ row.unit }}</div>
                                </td>
                                <td class="py-2 px-2 text-right text-slate-500">{{ row.planned }}</td>
                                <td class="py-2 px-2 text-right">
                                    <input v-model.number="row.consumed" type="number" min="0" step="1" class="w-20 rounded-md border-slate-300 text-right text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                </td>
                                <td class="py-2 px-2 text-right">
                                    <input v-model.number="row.left_with_client" type="number" min="0" step="1" class="w-20 rounded-md border-slate-300 text-right text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                </td>
                                <td class="py-2 pl-2 text-right font-medium text-slate-700">{{ returned(row) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p v-if="completionError" class="mt-3 text-sm font-medium text-red-600">
                    Consumul plus ce rămâne la client nu poate depăși cantitatea planificată.
                </p>

                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="showCompletion = false">
                        Anulează
                    </button>
                    <button type="button" :disabled="completionError" class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-500 disabled:cursor-not-allowed disabled:opacity-50" @click="submitCompletion">
                        Finalizează
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>