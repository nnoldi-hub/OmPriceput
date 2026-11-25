<script setup>
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    form: Object,
    clients: Array,
    equipment: Array,
    services: Array,
    types: Object,
    visits: { type: Array, default: () => [] },
    chapters: { type: Array, default: () => [] },
    chapterSuggestions: { type: Array, default: () => [] },
    equipmentUnits: { type: Array, default: () => [] },
    serviceUnits: { type: Array, default: () => [] },
    minimumMargin: { type: Number, default: 20 },
    vatPercentage: { type: Number, default: 0 },
});

const SECTIONS = [
    {
        key: 'materials',
        title: 'A. Materiale necesare',
        hint: 'Materialele pe care le furnizezi tu.',
        source: 'equipment',
        priced: true,
    },
    {
        key: 'labor',
        title: 'B. Manoperă',
        hint: 'Serviciile prestate de echipa.',
        source: 'service',
        priced: true,
    },
    {
        key: 'client_materials',
        title: 'C. Materiale achizitionate de client',
        hint: 'Doar lista informativa. Nu intra in total.',
        source: null,
        priced: false,
    },
];

const searchTerm = ref('');
const activeSearchRef = ref(null);
let tempCounter = 0;

const quick = reactive({
    open: false,
    chapterRef: null,
    section: 'materials',
    type: 'material',
    name: '',
    unit: '',
    quantity: 1,
    unit_price: 0,
    unit_cost: null,
    save_to_catalog: true,
});

const visibleChapters = computed(() => (props.form.chapters ?? []).filter((chapter) => ! chapter.removed));

const catalogEntries = computed(() => [
    ...(props.equipment ?? []).map((item) => ({
        key: `equipment-${item.id}`,
        id: item.id,
        catalog_type: 'material',
        source: 'equipment_id',
        name: item.name,
        unit: item.unit ?? '',
        price: Number(item.unit_price) || 0,
        cost: Number(item.cost_price) > 0 ? Number(item.cost_price) : null,
    })),
    ...(props.services ?? []).map((item) => ({
        key: `service-${item.id}`,
        id: item.id,
        catalog_type: 'serviciu',
        source: 'service_id',
        name: item.name,
        unit: item.unit ?? '',
        price: Number(item.sale_price) || 0,
        cost: Number(item.cost_price) > 0 ? Number(item.cost_price) : null,
    })),
]);

function normalize(value) {
    return String(value ?? '').trim().toLocaleLowerCase('ro');
}

const searchResults = computed(() => {
    const term = normalize(searchTerm.value);
    if (! term) return [];

    return catalogEntries.value.filter((entry) => normalize(entry.name).includes(term)).slice(0, 12);
});

function isSearching(chapterRef) {
    return activeSearchRef.value === chapterRef && String(searchTerm.value).trim().length > 0;
}

function addCatalogItem(entry, chapterRef) {
    props.form.items.push({
        equipment_id: entry.catalog_type === 'material' ? entry.id : null,
        service_id: entry.catalog_type === 'serviciu' ? entry.id : null,
        chapter_ref: chapterRef ?? null,
        section: entry.catalog_type === 'material' ? 'materials' : 'labor',
        description: entry.name,
        quantity: 1,
        unit: entry.unit,
        unit_price: entry.price,
        unit_cost: entry.cost,
        save_to_catalog: false,
    });

    searchTerm.value = '';
    activeSearchRef.value = null;
}

function addItem(section, chapterRef, name = '') {
    props.form.items.push({
        equipment_id: null,
        service_id: null,
        chapter_ref: chapterRef ?? null,
        section,
        description: name,
        quantity: 1,
        unit: '',
        unit_price: 0,
        unit_cost: null,
        save_to_catalog: false,
    });
}

function removeItem(index) {
    props.form.items.splice(index, 1);
}

function itemsForChapter(chapterRef) {
    return props.form.items
        .map((item, index) => ({ item, index }))
        .filter(({ item }) => (item.chapter_ref ?? null) === (chapterRef ?? null));
}

function itemsForSection(chapterRef, section) {
    return itemsForChapter(chapterRef).filter(({ item }) => (item.section || 'labor') === section);
}

function applyEquipment(item) {
    item.service_id = null;
    item.save_to_catalog = false;
    const found = props.equipment.find((entry) => entry.id === item.equipment_id);
    if (found) {
        item.description = found.name;
        item.unit = found.unit;
        item.unit_price = Number(found.unit_price);
        item.unit_cost = Number(found.cost_price) > 0 ? Number(found.cost_price) : null;
    }
}

function applyService(item) {
    item.equipment_id = null;
    item.save_to_catalog = false;
    const found = props.services.find((entry) => entry.id === item.service_id);
    if (found) {
        item.description = found.name;
        item.unit = found.unit;
        item.unit_price = Number(found.sale_price);
        item.unit_cost = Number(found.cost_price) > 0 ? Number(found.cost_price) : null;
    }
}

function openQuickCreate(chapterRef, section, name = '') {
    Object.assign(quick, {
        open: true,
        chapterRef: chapterRef ?? null,
        section,
        type: section === 'labor' ? 'serviciu' : 'material',
        name: name || String(searchTerm.value).trim(),
        unit: '',
        quantity: 1,
        unit_price: 0,
        unit_cost: null,
        save_to_catalog: true,
    });
    searchTerm.value = '';
    activeSearchRef.value = null;
}

function changeQuickType() {
    quick.section = quick.type === 'serviciu' ? 'labor' : 'materials';
    quick.unit = '';
}

function quickUnits() {
    return quick.type === 'serviciu' ? props.serviceUnits : props.equipmentUnits;
}

function confirmQuickCreate() {
    const name = String(quick.name).trim();
    if (! name) return;

    props.form.items.push({
        equipment_id: null,
        service_id: null,
        chapter_ref: quick.chapterRef,
        section: quick.section,
        description: name,
        quantity: Math.max(1, Number(quick.quantity) || 1),
        unit: quick.unit,
        unit_price: Number(quick.unit_price) || 0,
        unit_cost: quick.unit_cost === '' || quick.unit_cost === null ? null : Number(quick.unit_cost),
        save_to_catalog: quick.save_to_catalog,
    });

    quick.open = false;
}

function addChapter() {
    props.form.chapters ??= [];
    tempCounter += 1;
    props.form.chapters.push({
        ref: `tmp-${Date.now()}-${tempCounter}`,
        id: null,
        name: '',
        position: props.form.chapters.length,
        removed: false,
    });
}

function removeChapter(chapter) {
    const count = itemsForChapter(chapter.ref).length;
    const label = chapter.name || 'fără nume';
    if (! confirm(`Ștergi capitolul „${label}”? Liniile vor fi mutate în capitolul „General”.${count ? ` (${count} linie/linii)` : ''}`)) {
        return;
    }

    for (const item of props.form.items) {
        if ((item.chapter_ref ?? null) === chapter.ref) {
            item.chapter_ref = null;
        }
    }

    if (chapter.id) {
        chapter.removed = true;
    } else {
        props.form.chapters.splice(props.form.chapters.indexOf(chapter), 1);
    }
}

function moveChapter(chapter, delta) {
    const list = props.form.chapters;
    const from = list.indexOf(chapter);
    const to = from + delta;
    if (from < 0 || to < 0 || to >= list.length) return;

    list.splice(from, 1);
    list.splice(to, 0, chapter);
    list.forEach((entry, index) => { entry.position = index; });
}

function isUncatalogued(item) {
    return item.section !== 'client_materials' && ! item.equipment_id && ! item.service_id;
}

function round2(value) {
    return Math.round((Number(value) || 0) * 100) / 100;
}

function lineCost(item) {
    if (item.unit_cost === '' || item.unit_cost === null || item.unit_cost === undefined) return null;
    const cost = Number(item.unit_cost);
    return Number.isFinite(cost) ? cost : null;
}

function itemMargin(item) {
    const cost = lineCost(item);
    const value = (Number(item.quantity) || 0) * (Number(item.unit_price) || 0);
    if (cost === null || value <= 0) return null;

    return round2(((value - (Number(item.quantity) || 0) * cost) / value) * 100);
}

const marginTotals = computed(() => {
    let value = 0;
    let cost = 0;
    let counted = 0;

    for (const item of props.form.items) {
        if (item.section === 'client_materials') continue;
        const unitCost = lineCost(item);
        if (unitCost === null) continue;

        const quantity = Number(item.quantity) || 0;
        value += quantity * (Number(item.unit_price) || 0);
        cost += quantity * unitCost;
        counted += 1;
    }

    if (! counted || value <= 0) return null;

    return {
        percent: round2(((value - cost) / value) * 100),
        amount: round2(value - cost),
    };
});

const subtotals = computed(() => {
    const result = { materials: 0, labor: 0, client_materials: 0 };

    for (const item of props.form.items) {
        const section = item.section || 'labor';
        if (section === 'client_materials') continue;
        result[section] = round2(result[section] + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0));
    }

    return result;
});

const total = computed(() => round2(subtotals.value.materials + subtotals.value.labor));
const vatIncluded = computed(() => props.form.vat_applied !== false && props.vatPercentage > 0);
const vatAmount = computed(() => vatIncluded.value ? round2(total.value * props.vatPercentage / 100) : 0);
const totalWithVat = computed(() => round2(total.value + vatAmount.value));

function sectionSubtotal(chapterRef, sectionKey) {
    return round2(itemsForSection(chapterRef, sectionKey).reduce((sum, { item }) => (
        sum + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0)
    ), 0));
}

function chapterSubtotal(chapterRef) {
    return round2(sectionSubtotal(chapterRef, 'materials') + sectionSubtotal(chapterRef, 'labor'));
}

function money(value) {
    return Number(value || 0).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>

<template>
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Client *</label>
                <select v-model.number="form.client_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option :value="null" disabled>Selecteaza client</option>
                    <option v-for="client in clients" :key="client.id" :value="client.id">
                        {{ client.name }}<template v-if="client.company_name"> ({{ client.company_name }})</template>
                    </option>
                </select>
                <p v-if="form.errors.client_id" class="mt-1 text-sm text-red-600">{{ form.errors.client_id }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Titlu deviz *</label>
                <input v-model="form.title" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tip lucrare *</label>
                <select v-model="form.job_type" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option v-for="(label, value) in types" :key="value" :value="value">{{ label }}</option>
                </select>
                <p v-if="form.errors.job_type" class="mt-1 text-sm text-red-600">{{ form.errors.job_type }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Constatare asociata</label>
                <select v-model.number="form.visit_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option :value="null">Fara constatare</option>
                    <option v-for="visit in visits" :key="visit.id" :value="visit.id">
                        {{ visit.label ?? `Constatare #${visit.id}` }}
                    </option>
                </select>
                <p v-if="form.errors.visit_id" class="mt-1 text-sm text-red-600">{{ form.errors.visit_id }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Status</label>
                <p class="mt-2 text-sm text-slate-600">Devizul se salvează ca draft și se trimite separat clientului.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Valabil până la</label>
                <input v-model="form.valid_until" type="date" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                <p class="mt-1 text-xs text-slate-500">Implicit: data emiterii + zilele configurate în setări.</p>
                <p v-if="form.errors.valid_until" class="mt-1 text-sm text-red-600">{{ form.errors.valid_until }}</p>
            </div>
        </div>

        <p v-if="form.errors.items" class="text-sm text-red-600">{{ form.errors.items }}</p>
        <p v-if="form.errors.chapters" class="text-sm text-red-600">{{ form.errors.chapters }}</p>

        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Capitole</h3>
                    <p class="text-xs text-slate-500">Liniile fără capitol rămân în „General”. Pagina 1 a PDF-ului este centralizatorul.</p>
                </div>
                <button type="button" class="rounded-md border border-blue-300 bg-white px-3 py-1.5 text-sm font-semibold text-blue-600 hover:bg-blue-50" @click="addChapter">
                    + Adaugă capitol
                </button>
            </div>
            <div v-if="chapterSuggestions.length" class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <span>Sugestii:</span>
                <button
                    v-for="name in chapterSuggestions"
                    :key="name"
                    type="button"
                    class="rounded-full border border-slate-300 bg-white px-2 py-0.5 hover:border-blue-400 hover:text-blue-600"
                    @click="addChapter(); form.chapters[form.chapters.length - 1].name = name"
                >
                    {{ name }}
                </button>
            </div>
        </div>

        <!-- Capitolul implicit: toate liniile vechi, fără chapter_id -->
        <div class="rounded-lg border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">General <span class="text-xs font-normal text-slate-500">(implicit)</span></h3>
                    <p class="text-xs text-slate-500">Liniile fără capitol, inclusiv din devizele vechi.</p>
                </div>
                <span class="text-sm text-slate-500">Subtotal: <strong class="text-slate-900">{{ money(chapterSubtotal(null)) }} lei</strong></span>
            </div>
            <div class="space-y-4 p-4">
                <div class="relative">
                    <input
                        :value="activeSearchRef === null ? searchTerm : ''"
                        type="search"
                        placeholder="Caută serviciu sau material în catalog..."
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        @focus="activeSearchRef = null"
                        @input="searchTerm = $event.target.value; activeSearchRef = null"
                    />
                    <div v-if="isSearching(null)" class="absolute z-20 mt-1 w-full rounded-md border border-slate-200 bg-white shadow-lg">
                        <button v-for="entry in searchResults" :key="entry.key" type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" @click="addCatalogItem(entry, null)">
                            {{ entry.name }} <span class="text-xs text-slate-500">· {{ entry.catalog_type }} · {{ money(entry.price) }} lei</span>
                        </button>
                        <button v-if="!searchResults.length" type="button" class="block w-full px-3 py-2 text-left text-sm font-medium text-blue-600 hover:bg-blue-50" @click="openQuickCreate(null, 'materials')">
                            + Creează «{{ searchTerm.trim() }}»
                        </button>
                    </div>
                </div>

                <div v-for="section in SECTIONS" :key="`general-${section.key}`" class="rounded-lg border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 bg-white px-3 py-2">
                        <div>
                            <h4 class="text-xs font-semibold text-slate-700">{{ section.title }}</h4>
                            <p class="text-[11px] text-slate-500">{{ section.hint }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span v-if="section.priced" class="text-xs text-slate-500">Subtotal: <strong>{{ money(sectionSubtotal(null, section.key)) }}</strong></span>
                            <button type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-500" @click="addItem(section.key, null)">+ Adaugă</button>
                            <button v-if="section.priced" type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-500" @click="openQuickCreate(null, section.key)">+ Creează</button>
                        </div>
                    </div>
                    <div class="space-y-2 p-3">
                        <div v-for="{ item, index } in itemsForSection(null, section.key)" :key="index" class="grid grid-cols-12 gap-2">
                            <div v-if="section.source" class="col-span-12 sm:col-span-3">
                                <select
                                    v-if="section.source === 'equipment'"
                                    v-model.number="item.equipment_id"
                                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    @change="applyEquipment(item)"
                                >
                                    <option :value="null">Material personalizat</option>
                                    <option v-for="eq in equipment" :key="eq.id" :value="eq.id">{{ eq.name }}</option>
                                </select>
                                <select
                                    v-else
                                    v-model.number="item.service_id"
                                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    @change="applyService(item)"
                                >
                                    <option :value="null">Manoperă personalizată</option>
                                    <option v-for="service in services" :key="service.id" :value="service.id">{{ service.name }}</option>
                                </select>
                            </div>
                            <div :class="section.source ? 'col-span-12 sm:col-span-4' : 'col-span-12 sm:col-span-6'">
                                <input v-model="item.description" type="text" placeholder="Descriere" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                <span v-if="isUncatalogued(item)" class="mt-1 inline-block rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800">necatalogat</span>
                            </div>
                            <div class="col-span-4 sm:col-span-1">
                                <input v-model.number="item.quantity" type="number" min="1" placeholder="Cant." class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div class="col-span-4 sm:col-span-1">
                                <input v-model="item.unit" type="text" placeholder="UM" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <template v-if="section.priced">
                                <div class="col-span-6 sm:col-span-2">
                                    <input v-model.number="item.unit_price" type="number" min="0" step="0.01" placeholder="Preț unitar" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                </div>
                                <div class="col-span-6 sm:col-span-2">
                                    <input v-model="item.unit_cost" type="number" min="0" step="0.01" placeholder="Cost unitar" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                </div>
                                <div class="col-span-12 sm:col-span-2 text-sm text-slate-600">
                                    <div>{{ money((Number(item.quantity) || 0) * (Number(item.unit_price) || 0)) }} lei</div>
                                    <div v-if="itemMargin(item) !== null" class="text-[11px]" :class="itemMargin(item) < minimumMargin ? 'text-red-600' : 'text-emerald-600'">
                                        marjă {{ money(itemMargin(item)) }}%
                                    </div>
                                </div>
                            </template>
                            <div class="col-span-4 sm:col-span-1 flex items-center justify-end">
                                <button type="button" class="text-red-500 hover:text-red-700" @click="removeItem(index)">&times;</button>
                            </div>
                        </div>
                        <p v-if="!itemsForSection(null, section.key).length" class="text-sm text-slate-400">Nicio linie în această secțiune.</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-for="chapter in visibleChapters" :key="chapter.ref" class="rounded-lg border border-slate-200">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                <div class="min-w-64 flex-1">
                    <input
                        v-model="chapter.name"
                        type="text"
                        placeholder="Nume capitol (ex: Instalații electrice)"
                        :list="`chapter-suggestions-${chapter.ref}`"
                        class="block w-full rounded-md border-slate-300 text-sm font-semibold shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <datalist :id="`chapter-suggestions-${chapter.ref}`">
                        <option v-for="name in chapterSuggestions" :key="name" :value="name"></option>
                    </datalist>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-slate-500">Subtotal: <strong class="text-slate-900">{{ money(chapterSubtotal(chapter.ref)) }} lei</strong></span>
                    <button type="button" class="rounded border border-slate-300 bg-white px-2 py-1 text-xs text-slate-600 hover:bg-slate-100" title="Mută capitolul în sus" @click="moveChapter(chapter, -1)">↑</button>
                    <button type="button" class="rounded border border-slate-300 bg-white px-2 py-1 text-xs text-slate-600 hover:bg-slate-100" title="Mută capitolul în jos" @click="moveChapter(chapter, 1)">↓</button>
                    <button type="button" class="rounded border border-red-300 bg-white px-2 py-1 text-xs text-red-600 hover:bg-red-50" @click="removeChapter(chapter)">Șterge</button>
                </div>
            </div>
            <div class="space-y-4 p-4">
                <div class="relative">
                    <input
                        :value="activeSearchRef === chapter.ref ? searchTerm : ''"
                        type="search"
                        placeholder="Caută serviciu sau material în catalog..."
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        @focus="activeSearchRef = chapter.ref"
                        @input="searchTerm = $event.target.value; activeSearchRef = chapter.ref"
                    />
                    <div v-if="isSearching(chapter.ref)" class="absolute z-20 mt-1 w-full rounded-md border border-slate-200 bg-white shadow-lg">
                        <button v-for="entry in searchResults" :key="entry.key" type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" @click="addCatalogItem(entry, chapter.ref)">
                            {{ entry.name }} <span class="text-xs text-slate-500">· {{ entry.catalog_type }} · {{ money(entry.price) }} lei</span>
                        </button>
                        <button v-if="!searchResults.length" type="button" class="block w-full px-3 py-2 text-left text-sm font-medium text-blue-600 hover:bg-blue-50" @click="openQuickCreate(chapter.ref, 'materials')">
                            + Creează «{{ searchTerm.trim() }}»
                        </button>
                    </div>
                </div>

                <div v-for="section in SECTIONS" :key="`${chapter.ref}-${section.key}`" class="rounded-lg border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 bg-white px-3 py-2">
                        <div>
                            <h4 class="text-xs font-semibold text-slate-700">{{ section.title }}</h4>
                            <p class="text-[11px] text-slate-500">{{ section.hint }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span v-if="section.priced" class="text-xs text-slate-500">Subtotal: <strong>{{ money(sectionSubtotal(chapter.ref, section.key)) }}</strong></span>
                            <button type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-500" @click="addItem(section.key, chapter.ref)">+ Adaugă</button>
                            <button v-if="section.priced" type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-500" @click="openQuickCreate(chapter.ref, section.key)">+ Creează</button>
                        </div>
                    </div>
                    <div class="space-y-2 p-3">
                        <div v-for="{ item, index } in itemsForSection(chapter.ref, section.key)" :key="index" class="grid grid-cols-12 gap-2">
                            <div v-if="section.source" class="col-span-12 sm:col-span-3">
                                <select
                                    v-if="section.source === 'equipment'"
                                    v-model.number="item.equipment_id"
                                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    @change="applyEquipment(item)"
                                >
                                    <option :value="null">Material personalizat</option>
                                    <option v-for="eq in equipment" :key="eq.id" :value="eq.id">{{ eq.name }}</option>
                                </select>
                                <select
                                    v-else
                                    v-model.number="item.service_id"
                                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    @change="applyService(item)"
                                >
                                    <option :value="null">Manoperă personalizată</option>
                                    <option v-for="service in services" :key="service.id" :value="service.id">{{ service.name }}</option>
                                </select>
                            </div>
                            <div :class="section.source ? 'col-span-12 sm:col-span-4' : 'col-span-12 sm:col-span-6'">
                                <input v-model="item.description" type="text" placeholder="Descriere" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                <span v-if="isUncatalogued(item)" class="mt-1 inline-block rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800">necatalogat</span>
                            </div>
                            <div class="col-span-4 sm:col-span-1">
                                <input v-model.number="item.quantity" type="number" min="1" placeholder="Cant." class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div class="col-span-4 sm:col-span-1">
                                <input v-model="item.unit" type="text" placeholder="UM" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <template v-if="section.priced">
                                <div class="col-span-6 sm:col-span-2">
                                    <input v-model.number="item.unit_price" type="number" min="0" step="0.01" placeholder="Preț unitar" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                </div>
                                <div class="col-span-6 sm:col-span-2">
                                    <input v-model="item.unit_cost" type="number" min="0" step="0.01" placeholder="Cost unitar" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                </div>
                                <div class="col-span-12 sm:col-span-2 text-sm text-slate-600">
                                    <div>{{ money((Number(item.quantity) || 0) * (Number(item.unit_price) || 0)) }} lei</div>
                                    <div v-if="itemMargin(item) !== null" class="text-[11px]" :class="itemMargin(item) < minimumMargin ? 'text-red-600' : 'text-emerald-600'">
                                        marjă {{ money(itemMargin(item)) }}%
                                    </div>
                                </div>
                            </template>
                            <div class="col-span-4 sm:col-span-1 flex items-center justify-end">
                                <button type="button" class="text-red-500 hover:text-red-700" @click="removeItem(index)">&times;</button>
                            </div>
                        </div>
                        <p v-if="!itemsForSection(chapter.ref, section.key).length" class="text-sm text-slate-400">Nicio linie în această secțiune.</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="quick.open" class="rounded-lg border border-blue-200 bg-blue-50 p-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-blue-900">Articol nou: «{{ quick.name }}»</h3>
                <button type="button" class="text-sm text-blue-700 hover:text-blue-900" @click="quick.open = false">Închide</button>
            </div>
            <p class="mt-1 text-xs text-blue-700">Linia intră imediat în deviz. Articolul se adaugă în catalog doar la salvarea devizului.</p>
            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="col-span-2 sm:col-span-4">
                    <label class="block text-xs font-medium text-slate-700">Denumire</label>
                    <input v-model="quick.name" type="text" placeholder="Denumirea articolului" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">Tip</label>
                    <select v-model="quick.type" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm" @change="changeQuickType">
                        <option value="material">Material</option>
                        <option value="serviciu">Serviciu</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">Unitate de măsură</label>
                    <input v-model="quick.unit" type="text" list="quick-units" placeholder="buc / ora" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm" />
                    <datalist id="quick-units">
                        <option v-for="unit in quickUnits()" :key="unit" :value="unit"></option>
                    </datalist>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">Cantitate</label>
                    <input v-model.number="quick.quantity" type="number" min="1" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">Preț de vânzare</label>
                    <input v-model.number="quick.unit_price" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">Cost (opțional)</label>
                    <input v-model="quick.unit_cost" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm" />
                </div>
                <label class="col-span-2 flex items-center gap-2 self-end text-sm text-slate-700 sm:col-span-4">
                    <input v-model="quick.save_to_catalog" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                    Salvează în catalog
                </label>
            </div>
            <div class="mt-4 flex justify-end">
                <button type="button" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500" @click="confirmQuickCreate">
                    Adaugă linia în deviz
                </button>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Note interne</label>
            <textarea v-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
        </div>

        <div class="space-y-2 border-t border-slate-200 pt-4">
            <label v-if="vatPercentage > 0" class="flex flex-wrap items-center gap-2 text-sm text-slate-700">
                <input v-model="form.vat_applied" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                Aplică TVA ({{ vatPercentage }}%)
                <span v-if="!form.vat_applied" class="rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Ofertă fără TVA</span>
            </label>
            <div class="flex flex-wrap justify-end gap-6 text-sm text-slate-500">
                <span>Materiale: <strong class="text-slate-800">{{ money(subtotals.materials) }} lei</strong></span>
                <span>Manoperă: <strong class="text-slate-800">{{ money(subtotals.labor) }} lei</strong></span>
                <span>{{ vatIncluded ? 'Total fără TVA' : 'Total' }}: <strong class="text-lg font-bold text-slate-900">{{ money(total) }} lei</strong></span>
            </div>
            <div v-if="vatIncluded" class="flex justify-end gap-6 text-sm text-slate-500">
                <span>TVA ({{ vatPercentage }}%): <strong class="text-slate-800">{{ money(vatAmount) }} lei</strong></span>
                <span>Total cu TVA: <strong class="text-slate-900">{{ money(totalWithVat) }} lei</strong></span>
            </div>
            <div v-else-if="vatPercentage > 0" class="flex justify-end text-sm text-amber-700">
                Fără TVA — operațiune scutită / regim special.
            </div>
            <div v-if="marginTotals" class="rounded-md px-3 py-2 text-sm" :class="marginTotals.percent < minimumMargin ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'">
                Marjă totală: <strong>{{ money(marginTotals.percent) }}%</strong> ({{ money(marginTotals.amount) }} lei)
                <span v-if="marginTotals.percent < minimumMargin">— sub pragul minim de {{ money(minimumMargin) }}%</span>
            </div>
        </div>
    </div>
</template>
