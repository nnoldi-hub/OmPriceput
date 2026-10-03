<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import InstallationForm from './Partials/InstallationForm.vue';

const props = defineProps({
    clients: Array,
    offers: Array,
    technicians: Array,
    equipment: Array,
    services: Array,
    types: Object,
    statuses: Object,
    preselectedClientId: Number,
});

const form = useForm({
    client_id: props.preselectedClientId ?? null,
    offer_id: null,
    technician_id: null,
    type: 'instalare',
    requested_type: null,
    address: '',
    latitude: null,
    longitude: null,
    scheduled_at: '',
    status: 'scheduled',
    notes: '',
    labor_hours: null,
    materials: '',
    material_items: [],
    service_items: [],
    customer_name: '',
    customer_notes: '',
    handover_at: '',
    technician_signature: null,
    customer_signature: null,
    photos: [],
});

function submit() {
    form.post(route('technical.installations.store'), { forceFormData: true });
}
</script>

<template>
    <Head title="Programare noua" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Programare noua</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <form class="space-y-6 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <InstallationForm :form="form" :clients="clients" :offers="offers" :technicians="technicians" :equipment="equipment" :services="services" :types="types" :statuses="statuses" />
                    <div class="flex justify-end gap-3">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50">
                            Salveaza programarea
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
