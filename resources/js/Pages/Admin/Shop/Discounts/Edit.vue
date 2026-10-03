<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ discount: Object, equipment: Array, categories: Object });

const form = useForm({
    name: props.discount.name,
    scope: props.discount.scope,
    equipment_id: props.discount.equipment_id ?? '',
    category: props.discount.category ?? 'consumabil',
    type: props.discount.type,
    value: Number(props.discount.value),
    starts_at: props.discount.starts_at ? props.discount.starts_at.substring(0, 10) : '',
    ends_at: props.discount.ends_at ? props.discount.ends_at.substring(0, 10) : '',
    is_active: props.discount.is_active,
});

function submit() {
    form.put(route('admin.discounts.update', props.discount.id));
}
</script>

<template>
    <Head title="Editeaza reducere" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Editeaza reducere</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
                <form class="space-y-6 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Nume *</label>
                        <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Se aplica la</label>
                        <select v-model="form.scope" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="product">Un singur produs</option>
                            <option value="category">Toata categoria</option>
                        </select>
                    </div>

                    <div v-if="form.scope === 'product'">
                        <label class="block text-sm font-medium text-slate-700">Produs</label>
                        <select v-model="form.equipment_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="">Selecteaza produsul</option>
                            <option v-for="item in equipment" :key="item.id" :value="item.id">{{ item.name }}</option>
                        </select>
                        <p v-if="form.errors.equipment_id" class="mt-1 text-sm text-red-600">{{ form.errors.equipment_id }}</p>
                    </div>

                    <div v-else>
                        <label class="block text-sm font-medium text-slate-700">Categorie</label>
                        <select v-model="form.category" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option v-for="(label, value) in categories" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Tip reducere</label>
                            <select v-model="form.type" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                                <option value="percent">Procent (%)</option>
                                <option value="fixed">Valoare fixa (lei)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Valoare</label>
                            <input v-model.number="form.value" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                            <p v-if="form.errors.value" class="mt-1 text-sm text-red-600">{{ form.errors.value }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Incepe la (optional)</label>
                            <input v-model="form.starts_at" type="date" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Se termina la (optional)</label>
                            <input v-model="form.ends_at" type="date" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                            <p v-if="form.errors.ends_at" class="mt-1 text-sm text-red-600">{{ form.errors.ends_at }}</p>
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-blue-600" />
                        Reducere activa
                    </label>

                    <div class="flex justify-end">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50">
                            Salveaza modificarile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
