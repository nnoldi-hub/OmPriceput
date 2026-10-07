<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ service: Object, trades: Object, units: Array });

const form = useForm({
    name: props.service.name,
    category: props.service.category,
    unit: props.service.unit,
    cost_price: Number(props.service.cost_price),
    sale_price: Number(props.service.sale_price),
    duration_minutes: Number(props.service.duration_minutes ?? 60),
    description: props.service.description ?? '',
    is_active: props.service.is_active,
});

function submit() { form.put(route('technical.services.update', props.service.id)); }
</script>
<template>
    <Head title="Editează serviciu" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold leading-tight text-gray-800">Editează serviciu</h2></template>
        <div class="py-8"><div class="mx-auto max-w-2xl sm:px-6 lg:px-8"><form class="space-y-6 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Nume *</label>
                    <input v-model="form.name" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                    <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Categorie</label>
                    <select v-model="form.category" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                        <option v-for="(label, key) in trades" :key="key" :value="key">{{ label }}</option>
                    </select>
                    <p v-if="form.errors.category" class="text-sm text-red-600">{{ form.errors.category }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Unitate</label>
                    <select v-model="form.unit" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                        <option v-for="u in units" :key="u" :value="u">{{ u }}</option>
                    </select>
                    <p v-if="form.errors.unit" class="text-sm text-red-600">{{ form.errors.unit }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Preț cost</label>
                    <input v-model.number="form.cost_price" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Preț vânzare</label>
                    <input v-model.number="form.sale_price" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Durată estimată (minute)</label>
                    <input v-model.number="form.duration_minutes" type="number" min="5" max="600" step="5" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                    <p v-if="form.errors.duration_minutes" class="text-sm text-red-600">{{ form.errors.duration_minutes }}</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Descriere</label>
                    <textarea v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600 sm:col-span-2"><input v-model="form.is_active" type="checkbox" /> Activ</label>
            </div>
            <div class="flex justify-end"><button :disabled="form.processing" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Salvează modificările</button></div>
        </form></div></div>
    </AuthenticatedLayout>
</template>