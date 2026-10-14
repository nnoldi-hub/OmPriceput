<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const { canManage } = usePermissions();

const props = defineProps({
    rows: Array,
    totals: Object,
    meta: Object,
    filters: Object,
    installations: Array,
    suppliers: Array,
});

const form = reactive({
    installation_id: props.filters.installation_id ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    only_missing: props.filters.only_missing ?? false,
    include_unscheduled: props.filters.include_unscheduled ?? true,
    include_covered: props.filters.include_covered ?? false,
    supplier_id: props.filters.supplier_id ?? '',
    all: props.filters.all ?? false,
});

function query(overrides = {}) {
    const state = { ...form, ...overrides };
    const params = {};
    if (state.installation_id) params.installation_id = state.installation_id;
    if (state.from) params.from = state.from;
    if (state.to) params.to = state.to;
    if (state.only_missing) params.only_missing = 1;
    if (state.include_covered) params.include_covered = 1;
    if (state.supplier_id) params.supplier_id = state.supplier_id;
    if (state.all) {
        params.all = 1;
    } else if (!state.include_unscheduled) {
        params.include_unscheduled = 0;
    }
    return params;
}

function apply(overrides = {}) {
    Object.assign(form, overrides);
    router.get(route('technical.requirements.index'), query(), { preserveScroll: true, replace: true });
}

function preset(kind) {
    const today = new Date();
    const fmt = (d) => d.toISOString().slice(0, 10);
    if (kind === 'today') {
        return apply({ from: fmt(today), to: fmt(today), all: false });
    }
    if (kind === 'week') {
        const day = (today.getDay() + 6) % 7;
        const start = new Date(today);
        start.setDate(today.getDate() - day);
        const end = new Date(start);
        end.setDate(start.getDate() + 6);
        return apply({ from: fmt(start), to: fmt(end), all: false });
    }
    if (kind === 'month') {
        const start = new Date(today.getFullYear(), today.getMonth(), 1);
        const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        return apply({ from: fmt(start), to: fmt(end), all: false });
    }
    return apply({ from: '', to: '', all: true });
}

const exportQuery = computed(() => new URLSearchParams(query()).toString());
const pdfUrl = computed(() => `${route('technical.requirements.pdf')}?${exportQuery.value}`);
const excelUrl = computed(() => `${route('technical.requirements.excel')}?${exportQuery.value}`);

function money(value) {
    return Number(value ?? 0).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function qty(value) {
    return Number(value ?? 0).toLocaleString('ro-RO', { maximumFractionDigits: 2 });
}

function catalogSources(row) {
    return (row.sources ?? []).filter((source) => source.offer_item_id);
}

function addToCatalog(source) {
    if (!source?.offer_item_id) return;
    router.post(route('technical.requirements.items.catalog', source.offer_item_id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Necesar materiale" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Necesar materiale</h2>
                    <p class="text-sm text-slate-500">Calculat din oferte acceptate și programări viitoare, comparat cu stocul.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link :href="route('technical.requirements.shopping', query())" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                        Lista de cumpărat
                    </Link>
                    <a :href="pdfUrl" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">PDF</a>
                    <a :href="excelUrl" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Excel</a>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-5 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
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
                        <label class="text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase text-slate-500">Furnizor</span>
                            <select v-model="form.supplier_id" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" @change="apply()">
                                <option value="">Toți furnizorii</option>
                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                            </select>
                        </label>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <button type="button" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50" @click="preset('today')">Azi</button>
                        <button type="button" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50" @click="preset('week')">Săptămâna aceasta</button>
                        <button type="button" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50" @click="preset('month')">Luna aceasta</button>
                        <button type="button" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50" @click="preset('all')">Toate</button>

                        <label class="ml-2 flex items-center gap-2 text-sm text-slate-600">
                            <input v-model="form.only_missing" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" @change="apply()" />
                            Doar de cumpărat
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input v-model="form.include_unscheduled" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" :disabled="form.all" @change="apply()" />
                            Include neprogramate
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input v-model="form.include_covered" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" @change="apply()" />
                            Include și acoperite (la export)
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-xs uppercase text-slate-500">Articole</div>
                        <div class="text-2xl font-semibold text-slate-800">{{ totals.lines }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-xs uppercase text-slate-500">De cumpărat</div>
                        <div class="text-2xl font-semibold text-amber-600">{{ totals.to_buy_lines }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-xs uppercase text-slate-500">Perioadă</div>
                        <div class="text-sm font-medium text-slate-700">{{ meta.period_label }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-xs uppercase text-slate-500">Cost estimat cumpărare</div>
                        <div class="text-2xl font-semibold text-slate-800">{{ money(totals.estimated_cost) }} lei</div>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Material</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Necesar</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">În stoc</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">De cumpărat</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Cost est.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Furnizor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="row in rows" :key="row.key" :class="row.to_buy > 0 ? 'bg-amber-50/60' : ''">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">{{ row.name }}</div>
                                    <div class="text-xs text-slate-400">
                                        {{ row.unit }}
                                        <span v-if="!row.in_catalog" class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-amber-700">necatalogat</span>
                                        <span v-else-if="row.sources.length > 1" class="ml-1">{{ row.sources.length }} lucrări</span>
                                    </div>
                                    <div v-if="!row.in_catalog && canManage('equipment') && catalogSources(row).length" class="mt-1 flex flex-wrap gap-1">
                                        <button
                                            v-for="source in catalogSources(row)"
                                            :key="source.offer_item_id"
                                            type="button"
                                            class="rounded border border-blue-200 bg-blue-50 px-1.5 py-0.5 text-[11px] font-medium text-blue-700 hover:bg-blue-100"
                                            :title="source.label"
                                            @click="addToCatalog(source)"
                                        >
                                            Adaugă în catalog
                                        </button>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ qty(row.needed) }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ qty(row.in_stock) }}</td>
                                <td class="px-4 py-3 text-right font-semibold" :class="row.to_buy > 0 ? 'text-amber-700' : 'text-emerald-600'">
                                    {{ row.to_buy > 0 ? qty(row.to_buy) : 'acoperit' }}
                                </td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ money(row.estimated_cost) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ row.supplier_name || '—' }}</td>
                            </tr>
                            <tr v-if="!rows.length">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">
                                    Nimic de cumpărat. Stocul acoperă necesarul din perioada selectată.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="rows.length" class="bg-slate-50">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Total</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ qty(totals.to_buy) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ money(totals.estimated_cost) }} lei</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
