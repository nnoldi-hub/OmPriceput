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
    chapterSuggestions: { type: Array, default: () => [] },
    equipmentUnits: { type: Array, default: () => [] },
    serviceUnits: { type: Array, default: () => [] },
    minimumMargin: { type: Number, default: 20 },
    vatPercentage: { type: Number, default: 0 },
});

const form = useForm({
    client_id: props.preselectedClientId ?? null,
    visit_id: props.preselectedVisit?.id ?? null,
    job_type: props.preselectedJobType ?? 'instalare',
    title: 'Deviz lucrare',
    status: 'draft',
    valid_until: props.defaultValidUntil ?? '',
    vat_applied: true,
    notes: props.preselectedVisit?.label ? `Constatare: ${props.preselectedVisit.label}` : '',
    chapters: [],
    items: [{
        equipment_id: null,
        service_id: null,
        chapter_ref: null,
        section: 'materials',
        description: '',
        quantity: 1,
        unit: '',
        unit_price: 0,
        unit_cost: null,
        save_to_catalog: false,
    }],
});

function submit() {
    form.items = form.items.map((item) => ({
        ...item,
        chapter_ref: item.chapter_ref || null,
        unit_cost: item.unit_cost === '' || item.unit_cost === null ? null : Number(item.unit_cost),
    }));
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
                    <OfferForm
                        :form="form"
                        :clients="clients"
                        :equipment="equipment"
                        :services="services"
                        :types="types"
                        :visits="preselectedVisit ? [preselectedVisit] : []"
                        :chapter-suggestions="chapterSuggestions"
                        :equipment-units="equipmentUnits"
                        :service-units="serviceUnits"
                        :minimum-margin="minimumMargin"
                        :vat-percentage="vatPercentage"
                    />
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
