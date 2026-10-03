<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import InstallationForm from './Partials/InstallationForm.vue';

const props = defineProps({
    installation: Object,
    clients: Array,
    offers: Array,
    technicians: Array,
    equipment: Array,
    services: Array,
    types: Object,
    statuses: Object,
});

const form = useForm({
    client_id: props.installation.client_id,
    offer_id: props.installation.offer_id,
    technician_id: props.installation.technician_id,
    type: props.installation.type,
    requested_type: props.installation.requested_type ?? null,
    address: props.installation.address,
    latitude: props.installation.latitude,
    longitude: props.installation.longitude,
    scheduled_at: props.installation.scheduled_at ? props.installation.scheduled_at.substring(0, 16) : '',
    status: props.installation.status,
    notes: props.installation.notes,
    labor_hours: props.installation.labor_hours,
    materials: (props.installation.materials ?? []).join('\n'),
    material_items: props.installation.material_items ?? [],
    service_items: props.installation.service_items ?? [],
    customer_name: props.installation.customer_name ?? '',
    customer_notes: props.installation.customer_notes ?? '',
    handover_at: props.installation.handover_at ? props.installation.handover_at.substring(0, 16) : '',
    technician_signature: null,
    customer_signature: null,
    photos: [],
});

function submit() {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('technical.installations.update', props.installation.id), { forceFormData: true });
}
</script>

<template>
    <Head title="Editeaza programare" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Editeaza programare</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <form class="space-y-6 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <InstallationForm :form="form" :clients="clients" :offers="offers" :technicians="technicians" :equipment="equipment" :services="services" :types="types" :statuses="statuses" />
                    <div class="flex justify-end gap-3">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50">
                            Salveaza modificarile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
