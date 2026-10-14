<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const { canManage } = usePermissions();

const props = defineProps({
    groups: Array,
    totals: Object,
    meta: Object,
    filters: Object,
    installations: Array,
});

const form = reactive({
    installation_id: props.filters.installation_id ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    include_unscheduled: props.filters.include_unscheduled ?? true,
    all: props.filters.all ?? false,
});

const checked = reactive({});
const copied = ref(false);

function query(overrides = {}) {
    const state = { ...form, ...overrides };
    const params = {};
    if (state.installation_id) params.installation_id = state.installation_id;
    if (state.from) params.from = state.from;
    if (state.to) params.to = state.to;
    if (state.all) {
        params.all = 1;
    } else if (!state.include_unscheduled) {
        params.include_unscheduled = 0;
    }
    return params;
}

function apply(overrides = {}) {
    router.get(route('technical.requirements.shopping'), query(overrides), { preserveState: true, replace: true });
}

const exportQuery = computed(() => new URLSearchParams(query()).toString());
const pdfUrl = computed(() => `${route('technical.requirements.pdf')}?${exportQuery.value}`);
const excelUrl = computed(() => `${route('technical.requirements.excel')}?${exportQuery.value}`);

const allItems = computed(() => props.groups.flatMap((group) => group.items));

function isChecked(item) {
    return checked[item.key] ?? false;
}

function groupChecked(group) {
    return group.items.length > 0 && group.items.every((item) => isChecked(item));
}

function toggleGroup(group) {
    const next = !groupChecked(group);
    group.items.forEach((item) => {
        checked[item.key] = next;
    });
}

function checkAll(value) {
    allItems.value.forEach((item) => {
        checked[item.key] = value;
    });
}

function money(value) {
    return Number(value ?? 0).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function qty(value) {
    return Number(value ?? 0).toLocaleString('ro-RO', { maximumFractionDigits: 2 });
}

function copyText() {
    const anyChecked = allItems.value.some((item) => isChecked(item));
    const lines = [`LISTĂ DE CUMPĂRAT - ${props.meta.period_label}`, ''];

    props.groups.forEach((group) => {
        const items = group.items.filter((item) => !anyChecked || isChecked(item));
        if (!items.length) return;
        lines.push(group.supplier_name.toUpperCase());
        items.forEach((item) => {
            lines.push(`[${isChecked(item) ? 'x' : ' '}] ${qty(item.to_buy)} ${item.unit} - ${item.name}`);
        });
        lines.push('');
    });

    const total = props.groups
        .flatMap((group) => group.items.filter((item) => !anyChecked || isChecked(item)))
        .reduce((sum, item) => sum + Number(item.estimated_cost ?? 0), 0);
    lines.push(`Total estimat: ${money(total)} lei`);

    const text = lines.join('\n');

    const done = () => {
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    };

    if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(() => fallbackCopy(text, done));
    } else {
        fallbackCopy(text, done);
    }
}

function fallbackCopy(text, done) {
    const area = document.createElement('textarea');
    area.value = text;
    document.body.appendChild(area);
    area.select();
    document.execCommand('copy');
    document.body.removeChild(area);
    done();
}

function printPage() {
    window.print();
}

const supplierGroups = computed(() => props.groups.filter((group) => group.supplier_id));
const withoutSupplier = computed(() => props.groups.filter((group) => !group.supplier_id).reduce((sum, group) => sum + group.items.length, 0));

function generateOrders() {
    const count = supplierGroups.value.length;
    if (!count) return;
    if (!confirm(`Generezi ${count} comandă/comenzi draft către furnizori pentru materialele de cumpărat?`)) return;
    router.post(route('technical.requirements.purchase-orders'), query(), { preserveScroll: true });
}
</script>

<template>
    <Head title="Lista de cumpărat" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Lista de cumpărat</h2>
                    <p class="text-sm text-slate-500">{{ meta.period_label }} · doar ce nu ai în stoc.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 print:hidden">
                    <Link :href="route('technical.requirements.index', query())" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Necesar</Link>
                    <button v-if="canManage('equipment') && supplierGroups.length" type="button" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-500" @click="generateOrders">
                        Comandă la furnizori ({{ supplierGroups.length }})
                    </button>
                    <button type="button" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500" @click="copyText">
                        {{ copied ? 'Copiat!' : 'Copiază pentru WhatsApp' }}
                    </button>
                    <a :href="pdfUrl" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">PDF</a>
                    <a :href="excelUrl" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Excel</a>
                    <button type="button" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="printPage">Printează</button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-5 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-4 shadow-sm print:hidden">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <label class="text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase text-slate-500">Lucrare</span>
                            <select v-model="form.installation_id" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" @change="apply()">
                                <option value="">Toate programările</option>
                                <option v-for="item in installations" :key="item.id" :value="item.id">{{ item.label }}</option>
                            </select>
                        </label>
                        <label class="text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase text-slate-500">De la</span>
                            <input v-model="form.from" type="date" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" @change="apply()" />
                        </label>
                        <label class="text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase text-slate-500">Până la</span>
                            <input v-model="form.to" type="date" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" @change="apply()" />
                        </label>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <button type="button" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50" @click="checkAll(true)">Bifează tot</button>
                        <button type="button" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50" @click="checkAll(false)">Debifează tot</button>
                        <span class="text-xs text-slate-400">Copiază trimite articolele bifate; dacă nu bifezi nimic, le trimite pe toate.</span>
                    </div>
                </div>

                <div v-for="group in groups" :key="group.supplier_id ?? 'none'" class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <label class="flex cursor-pointer items-center gap-3 bg-slate-800 px-4 py-2.5 text-white">
                        <input type="checkbox" :checked="groupChecked(group)" class="rounded border-white/40 bg-white/10 text-blue-500 focus:ring-blue-400 print:hidden" @change="toggleGroup(group)" />
                        <span class="flex-1 font-semibold">{{ group.supplier_name }}</span>
                        <span class="text-sm text-slate-300">{{ money(group.estimated_cost) }} lei</span>
                    </label>
                    <ul class="divide-y divide-slate-100">
                        <li v-for="item in group.items" :key="item.key" class="flex items-center gap-3 px-4 py-3">
                            <input
                                type="checkbox"
                                :checked="isChecked(item)"
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 print:hidden"
                                @change="checked[item.key] = !checked[item.key]"
                            />
                            <div class="flex-1">
                                <div class="font-medium text-slate-800" :class="isChecked(item) ? 'line-through text-slate-400' : ''">{{ item.name }}</div>
                                <div class="text-xs text-slate-400">necesar {{ qty(item.needed) }} · în stoc {{ qty(item.in_stock) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold text-slate-800">{{ qty(item.to_buy) }} {{ item.unit }}</div>
                                <div class="text-xs text-slate-400">{{ money(item.estimated_cost) }} lei</div>
                            </div>
                        </li>
                    </ul>
                </div>

                <div v-if="!groups.length" class="rounded-lg bg-white p-10 text-center text-sm text-slate-400 shadow-sm">
                    Nimic de cumpărat. Stocul acoperă necesarul din perioada selectată.
                </div>

                <div v-if="groups.length" class="flex items-center justify-between rounded-lg bg-slate-900 px-4 py-3 text-white shadow-sm">
                    <span class="font-semibold">Total estimat</span>
                    <span class="text-lg font-semibold">{{ money(totals.estimated_cost) }} lei</span>
                </div>

                <p v-if="withoutSupplier > 0" class="text-xs text-slate-500">
                    {{ withoutSupplier }} articol(e) nu au furnizor asociat și nu intră în comenzile generate automat — cumpără-le manual sau leagă-le de un furnizor în catalog.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
