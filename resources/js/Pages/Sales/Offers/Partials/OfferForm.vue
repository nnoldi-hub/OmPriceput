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

function addItem() {
    props.form.items.push({ equipment_id: null, service_id: null, description: '', quantity: 1, unit_price: 0 });
}

function removeItem(index) {
    props.form.items.splice(index, 1);
}

function applyEquipment(item) {
    item.service_id = null;
    const eq = props.equipment.find((e) => e.id === item.equipment_id);
    if (eq) {
        item.description = eq.name;
        item.unit_price = Number(eq.unit_price);
    }
}

function applyService(item) {
    item.equipment_id = null;
    const service = props.services.find((s) => s.id === item.service_id);
    if (service) {
        item.description = service.name;
        item.unit_price = Number(service.sale_price);
    }
}

const total = computed(() =>
    props.form.items.reduce((sum, item) => sum + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0), 0)
);

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
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label class="block text-sm font-medium text-slate-700">Produse / servicii</label>
                <button type="button" class="text-sm font-semibold text-blue-600 hover:text-blue-500" @click="addItem">
                    + Adauga linie
                </button>
            </div>
            <p v-if="form.errors.items" class="mt-1 text-sm text-red-600">{{ form.errors.items }}</p>

            <div class="mt-3 space-y-3">
                <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-12 gap-2 rounded-md border border-slate-200 p-3">
                    <div class="col-span-12 sm:col-span-4">
                        <select
                            v-model.number="item.equipment_id"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            @change="applyEquipment(item)"
                        >
                            <option :value="null">Produs personalizat</option>
                            <option v-for="eq in equipment" :key="eq.id" :value="eq.id">{{ eq.name }}</option>
                        </select>
                    </div>
                    <div class="col-span-12 sm:col-span-4">
                        <select v-model.number="item.service_id" class="block w-full rounded-md border-slate-300 text-sm shadow-sm" @change="applyService(item)">
                            <option :value="null">Serviciu / manopera</option>
                            <option v-for="service in services" :key="service.id" :value="service.id">{{ service.name }}</option>
                        </select>
                    </div>
                    <div class="col-span-12 sm:col-span-4">
                        <input v-model="item.description" type="text" placeholder="Descriere" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div class="col-span-4 sm:col-span-1">
                        <input v-model.number="item.quantity" type="number" min="1" placeholder="Cant." class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <input v-model.number="item.unit_price" type="number" min="0" step="0.01" placeholder="Pret unitar" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div class="col-span-2 sm:col-span-1 flex items-center justify-end">
                        <button type="button" class="text-red-500 hover:text-red-700" @click="removeItem(index)">&times;</button>
                    </div>
                </div>
                <p v-if="!form.items.length" class="text-sm text-slate-400">Adauga cel putin o linie de produs/serviciu.</p>
            </div>

            <div class="mt-4 flex justify-end border-t border-slate-200 pt-4">
                <span class="text-sm text-slate-500">Total: </span>
                <span class="ml-2 text-lg font-bold text-slate-900">{{ money(total) }} lei</span>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Note interne</label>
            <textarea v-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
        </div>
    </div>
</template>
