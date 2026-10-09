<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import OfferForm from './Partials/OfferForm.vue';

const props = defineProps({
    clients: Array,
    equipment: Array,
    services: Array,
    types: Object,
    preselectedClientId: Number,
    preselectedVisit: Object,
    preselectedJobType: String,
    defaultValidUntil: String,
});

const form = useForm({
    client_id: props.preselectedClientId ?? null,
    visit_id: props.preselectedVisit?.id ?? null,
    job_type: props.preselectedJobType ?? 'instalare',
    title: 'Deviz lucrare',
    status: 'draft',
    valid_until: props.defaultValidUntil ?? '',
    notes: props.preselectedVisit?.label ? `Constatare: ${props.preselectedVisit.label}` : '',
    items: [{ equipment_id: null, service_id: null, section: 'materials', description: '', quantity: 1, unit: '', unit_price: 0 }],
});

function submit() {
    form.post(route('sales.offers.store'));
}
</script>

<template>
    <Head title="Oferta noua" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Deviz nou</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <form class="space-y-6 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <OfferForm :form="form" :clients="clients" :equipment="equipment" :services="services" :types="types" :visits="preselectedVisit ? [preselectedVisit] : []" />
                    <div class="flex justify-end gap-3">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50">
                            Salveaza deviz
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
