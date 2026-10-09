<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import OfferForm from './Partials/OfferForm.vue';

const props = defineProps({
    offer: Object,
    clients: Array,
    equipment: Array,
    services: Array,
    types: Object,
    visits: { type: Array, default: () => [] },
});

const form = useForm({
    client_id: props.offer.client_id,
    visit_id: props.offer.visit_id ?? null,
    job_type: props.offer.job_type ?? 'instalare',
    title: props.offer.title,
    status: props.offer.status,
    valid_until: props.offer.valid_until ? props.offer.valid_until.substring(0, 10) : '',
    notes: props.offer.notes,
    items: props.offer.items.map((item) => ({
        equipment_id: item.equipment_id,
        service_id: item.service_id,
        section: item.section || (item.equipment_id ? 'materials' : 'labor'),
        description: item.description,
        quantity: item.quantity,
        unit: item.unit ?? '',
        unit_price: Number(item.unit_price),
    })),
});

function submit() {
    form.put(route('sales.offers.update', props.offer.id));
}
</script>

<template>
    <Head title="Editeaza deviz" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Editeaza devizul #{{ offer.id }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <form class="space-y-6 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <OfferForm :form="form" :clients="clients" :equipment="equipment" :services="services" :types="types" :visits="visits" />
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
