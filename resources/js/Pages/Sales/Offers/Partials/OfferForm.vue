<script setup>
import { computed } from 'vue';

const props = defineProps({
    form: Object,
    clients: Array,
    equipment: Array,
    services: Array,
    types: Object,
    visits: { type: Array, default: () => [] },
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
        title: 'B. Manopera',
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

function addItem(section) {
    props.form.items.push({
        equipment_id: null,
        service_id: null,
        section,
        description: '',
        quantity: 1,
        unit: '',
        unit_price: 0,
    });
}

function removeItem(index) {
    props.form.items.splice(index, 1);
}

function itemsFor(section) {
    return props.form.items
        .map((item, index) => ({ item, index }))
        .filter(({ item }) => (item.section || 'labor') === section);
}

function applyEquipment(item) {
    item.service_id = null;
    const eq = props.equipment.find((e) => e.id === item.equipment_id);
    if (eq) {
        item.description = eq.name;
        item.unit = eq.unit;
        item.unit_price = Number(eq.unit_price);
    }
}

function applyService(item) {
    item.equipment_id = null;
    const service = props.services.find((s) => s.id === item.service_id);
    if (service) {
        item.description = service.name;
        item.unit = service.unit;
        item.unit_price = Number(service.sale_price);
    }
}

const subtotals = computed(() => {
    const result = { materials: 0, labor: 0, client_materials: 0 };

    for (const item of props.form.items) {
        const section = item.section || 'labor';
        if (section === 'client_materials') continue;
        result[section] += (Number(item.quantity) || 0) * (Number(item.unit_price) || 0);
    }

    return result;
});

const total = computed(() => subtotals.value.materials + subtotals.value.labor);

function money(value) {
    return Number(value).toLocaleString('ro-RO', { minimumFractionDigits: 2 });
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
                <p class="mt-2 text-sm text-slate-600">Devizul se salveaza ca draft si se trimite separat clientului.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Valabila pana la</label>
                <input v-model="form.valid_until" type="date" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                <p v-if="form.errors.valid_until" class="mt-1 text-sm text-red-600">{{ form.errors.valid_until }}</p>
            </div>
        </div>

        <p v-if="form.errors.items" class="text-sm text-red-600">{{ form.errors.items }}</p>

        <div v-for="section in SECTIONS" :key="section.key" class="rounded-lg border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">{{ section.title }}</h3>
                    <p class="text-xs text-slate-500">{{ section.hint }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <span v-if="section.priced" class="text-sm text-slate-500">
                        Subtotal: <strong class="text-slate-900">{{ money(subtotals[section.key]) }} lei</strong>
                    </span>
                    <button type="button" class="text-sm font-semibold text-blue-600 hover:text-blue-500" @click="addItem(section.key)">
                        + Adauga
                    </button>
                </div>
            </div>

            <div class="space-y-3 p-4">
                <div v-for="{ item, index } in itemsFor(section.key)" :key="index" class="grid grid-cols-12 gap-2">
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
                            <option :value="null">Manopera personalizata</option>
                            <option v-for="service in services" :key="service.id" :value="service.id">{{ service.name }}</option>
                        </select>
                    </div>
                    <div :class="section.source ? 'col-span-12 sm:col-span-4' : 'col-span-12 sm:col-span-6'">
                        <input v-model="item.description" type="text" placeholder="Descriere" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div class="col-span-4 sm:col-span-1">
                        <input v-model.number="item.quantity" type="number" min="1" placeholder="Cant." class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div class="col-span-4 sm:col-span-1">
                        <input v-model="item.unit" type="text" placeholder="UM" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <template v-if="section.priced">
                        <div class="col-span-6 sm:col-span-2">
                            <input v-model.number="item.unit_price" type="number" min="0" step="0.01" placeholder="Pret unitar" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div class="col-span-6 sm:col-span-2 flex items-center text-sm text-slate-600">
                            {{ money((Number(item.quantity) || 0) * (Number(item.unit_price) || 0)) }} lei
                        </div>
                    </template>
                    <div class="col-span-12 sm:col-span-1 flex items-center justify-end">
                        <button type="button" class="text-red-500 hover:text-red-700" @click="removeItem(index)">&times;</button>
                    </div>
                </div>
                <p v-if="!itemsFor(section.key).length" class="text-sm text-slate-400">Nicio linie in aceasta sectiune.</p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Note interne</label>
            <textarea v-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
        </div>

        <div class="flex justify-end gap-8 border-t border-slate-200 pt-4">
            <span class="text-sm text-slate-500">Materiale: <strong class="text-slate-800">{{ money(subtotals.materials) }} lei</strong></span>
            <span class="text-sm text-slate-500">Manopera: <strong class="text-slate-800">{{ money(subtotals.labor) }} lei</strong></span>
            <span class="text-sm text-slate-500">Total (A + B): <strong class="text-lg font-bold text-slate-900">{{ money(total) }} lei</strong></span>
        </div>
    </div>
</template>
