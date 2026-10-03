<script setup>
import { computed } from 'vue';

const props = defineProps({
    form: Object,
    clients: Array,
    offers: Array,
    technicians: Array,
    equipment: Array,
    services: Array,
    types: Object,
    statuses: Object,
});

const filteredOffers = computed(() => props.offers.filter((o) => o.client_id === props.form.client_id));

function useClientAddress() {
    const client = props.clients.find((c) => c.id === props.form.client_id);
    if (client && !props.form.address) {
        props.form.address = [client.address, client.city].filter(Boolean).join(', ');
    }
}

function addMaterial() {
    props.form.material_items.push({ equipment_id: null, quantity: 1 });
}

function removeMaterial(index) {
    props.form.material_items.splice(index, 1);
}

function addService() {
    props.form.service_items.push({ service_id: null, quantity: 1 });
}

function removeService(index) {
    props.form.service_items.splice(index, 1);
}
</script>

<template>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-slate-700">Client *</label>
            <select v-model.number="form.client_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" @change="useClientAddress">
                <option :value="null" disabled>Selecteaza client</option>
                <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
            </select>
            <p v-if="form.errors.client_id" class="mt-1 text-sm text-red-600">{{ form.errors.client_id }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Oferta acceptata (optional)</label>
            <select v-model.number="form.offer_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option :value="null">Fara oferta asociata</option>
                <option v-for="offer in filteredOffers" :key="offer.id" :value="offer.id">{{ offer.title }}</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Tip lucrare</label>
            <select v-model="form.type" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option v-for="(label, value) in types" :key="value" :value="value">{{ label }}</option>
            </select>
            <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Tip cerut de client</label>
            <select v-model="form.requested_type" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option :value="null">Nu stiu / n-a precizat</option>
                <option v-for="(label, value) in types" :key="value" :value="value">{{ label }}</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Meseras</label>
            <select v-model.number="form.technician_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option :value="null">Neasignat</option>
                <option v-for="tech in technicians" :key="tech.id" :value="tech.id">{{ tech.name }}</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-slate-700">Materiale din stoc</label>
            <div v-for="(item, index) in form.material_items" :key="index" class="mt-2 flex gap-2">
                <select v-model.number="item.equipment_id" class="block min-w-0 flex-1 rounded-md border-slate-300 shadow-sm">
                    <option :value="null" disabled>Selecteaza material</option>
                    <option v-for="stockItem in equipment" :key="stockItem.id" :value="stockItem.id">
                        {{ stockItem.name }} ({{ stockItem.stock_quantity }} {{ stockItem.unit }} disponibile)
                    </option>
                </select>
                <input v-model.number="item.quantity" type="number" min="1" class="w-24 rounded-md border-slate-300 shadow-sm" />
                <button type="button" class="rounded-md border border-red-200 px-3 text-red-600" @click="removeMaterial(index)">Sterge</button>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Servicii / manopera planificata</label>
                <div v-for="(item, index) in form.service_items" :key="index" class="mt-2 flex gap-2">
                    <select v-model.number="item.service_id" class="block min-w-0 flex-1 rounded-md border-slate-300 shadow-sm">
                        <option :value="null" disabled>Selecteaza serviciu</option>
                        <option v-for="service in services" :key="service.id" :value="service.id">{{ service.name }} ({{ service.unit }})</option>
                    </select>
                    <input v-model.number="item.quantity" type="number" min="1" class="w-24 rounded-md border-slate-300 shadow-sm" />
                    <button type="button" class="rounded-md border border-red-200 px-3 text-red-600" @click="removeService(index)">Sterge</button>
                </div>
                <button type="button" class="mt-2 rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-600" @click="addService">+ Adauga serviciu</button>
            </div>
            <button type="button" class="mt-2 rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-600" @click="addMaterial">+ Adauga material din stoc</button>
            <p class="mt-1 text-xs text-slate-400">Stocul se scade o singura data cand instalarea este marcata finalizata.</p>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-slate-700">Adresa</label>
            <input v-model="form.address" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Latitudine (optional)</label>
            <input v-model.number="form.latitude" type="number" step="0.0000001" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Longitudine (optional)</label>
            <input v-model.number="form.longitude" type="number" step="0.0000001" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Data si ora programate</label>
            <input v-model="form.scheduled_at" type="datetime-local" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Status</label>
            <select v-model="form.status" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="scheduled">Programata</option>
                <option value="in_progress">In desfasurare</option>
                <option value="completed">Finalizata</option>
                <option value="cancelled">Anulata</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-slate-700">Note tehnice</label>
            <textarea v-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
        </div>
        <div class="sm:col-span-2 border-t border-slate-200 pt-5">
            <h3 class="text-sm font-semibold text-slate-700">Date executie si proces-verbal</h3>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Ore lucrate</label>
            <input v-model.number="form.labor_hours" type="number" min="0" step="0.25" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Nume client la receptie</label>
            <input v-model="form.customer_name" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-slate-700">Materiale consumate (cate unul pe rand)</label>
            <textarea v-model="form.materials" rows="4" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-slate-700">Observatii client / receptie</label>
            <textarea v-model="form.customer_notes" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Data si ora receptiei</label>
            <input v-model="form.handover_at" type="datetime-local" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Semnatura tehnician (imagine)</label>
            <input type="file" accept="image/*" class="mt-1 block w-full text-sm text-slate-600" @change="form.technician_signature = $event.target.files[0] ?? null" />
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Semnatura client (imagine)</label>
            <input type="file" accept="image/*" class="mt-1 block w-full text-sm text-slate-600" @change="form.customer_signature = $event.target.files[0] ?? null" />
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-slate-700">Fotografii lucrare</label>
            <input type="file" accept="image/*" multiple class="mt-1 block w-full text-sm text-slate-600" @change="form.photos = [...$event.target.files]" />
            <p class="mt-1 text-xs text-slate-400">Maxim 5 MB per fotografie.</p>
        </div>
    </div>
</template>
