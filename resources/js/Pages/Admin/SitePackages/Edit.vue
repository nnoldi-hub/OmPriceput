<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ package: Object });
const form = useForm({ key: props.package.key, name: props.package.name, price_from: Number(props.package.price_from), vizite_an: props.package.vizite_an, timp_raspuns: props.package.timp_raspuns || '', prioritate: props.package.prioritate || '', features: (props.package.features || []).join('\n'), highlight: props.package.highlight, active: props.package.active, sort_order: props.package.sort_order });
function submit() { form.put(route('admin.site-packages.update', props.package.id)); }
</script>

<template>
    <Head :title="`Editeaza ${package.name}`" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold leading-tight text-gray-800">Editeaza pachet</h2></template>
        <div class="py-8"><div class="mx-auto max-w-2xl sm:px-6 lg:px-8"><form class="space-y-6 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div><label class="block text-sm font-medium text-slate-700">Cheie *</label><input v-model="form.key" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" /><p v-if="form.errors.key" class="mt-1 text-sm text-red-600">{{ form.errors.key }}</p></div>
                <div><label class="block text-sm font-medium text-slate-700">Nume *</label><input v-model="form.name" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" /></div>
                <div><label class="block text-sm font-medium text-slate-700">Pret pe an (lei) *</label><input v-model.number="form.price_from" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" /></div>
                <div><label class="block text-sm font-medium text-slate-700">Vizite pe an *</label><input v-model.number="form.vizite_an" type="number" min="0" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" /><p v-if="form.errors.vizite_an" class="mt-1 text-sm text-red-600">{{ form.errors.vizite_an }}</p></div>
                <div><label class="block text-sm font-medium text-slate-700">Timp de raspuns</label><input v-model="form.timp_raspuns" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" /></div>
                <div><label class="block text-sm font-medium text-slate-700">Prioritate</label><input v-model="form.prioritate" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" /></div>
                <div><label class="block text-sm font-medium text-slate-700">Ordine afisare</label><input v-model.number="form.sort_order" type="number" min="0" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" /></div>
                <div class="flex items-end gap-5 pb-2"><label class="flex items-center gap-2 text-sm"><input v-model="form.highlight" type="checkbox" /> Cel mai popular</label><label class="flex items-center gap-2 text-sm"><input v-model="form.active" type="checkbox" /> Activ</label></div>
                <div class="sm:col-span-2"><label class="block text-sm font-medium text-slate-700">Caracteristici (cate una pe rand) *</label><textarea v-model="form.features" rows="6" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" /><p v-if="form.errors.features" class="mt-1 text-sm text-red-600">{{ form.errors.features }}</p></div>
            </div>
            <div class="flex justify-end"><button :disabled="form.processing" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Salveaza modificarile</button></div>
        </form></div></div>
    </AuthenticatedLayout>
</template>
