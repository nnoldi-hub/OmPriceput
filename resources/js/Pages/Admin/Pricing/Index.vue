<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({ priceItems: Array, objectTypes: Object, units: Array });
const page = usePage();

const typeKeys = Object.keys(props.objectTypes);
const typeLabel = (key) => props.objectTypes[key]?.label ?? key;

const form = useForm({
    object_type: 'priza',
    name: '',
    unit: 'buc',
    price: 0,
    is_active: true,
});

function onTypeChange() {
    form.unit = props.objectTypes[form.object_type]?.unit ?? 'buc';
}

function submit() {
    form.post(route('admin.pricing.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('name', 'price'),
    });
}

function save(item) {
    router.put(route('admin.pricing.update', item.id), {
        object_type: item.object_type,
        name: item.name,
        unit: item.unit,
        price: item.price,
        is_active: item.is_active,
        position: item.position,
    }, { preserveScroll: true });
}

function destroy(item) {
    if (confirm(`Stergi tariful "${item.name}"?`)) {
        router.delete(route('admin.pricing.destroy', item.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Nomenclator preturi" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Nomenclator preturi</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div v-if="page.props.flash.success" class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ page.props.flash.success }}</div>

                <p class="mb-4 text-sm text-slate-500">Preturi de manopera pe tip de obiect. La conversia unei masuratori in oferta, fiecare obiect genereaza o linie folosind tariful de aici.</p>

                <form class="mb-6 grid grid-cols-2 gap-3 rounded-lg bg-white p-4 shadow-sm sm:grid-cols-6" @submit.prevent="submit">
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-medium text-slate-500">Tip</label>
                        <select v-model="form.object_type" class="mt-1 w-full rounded-md border-slate-300 text-sm" @change="onTypeChange">
                            <option v-for="key in typeKeys" :key="key" :value="key">{{ typeLabel(key) }}</option>
                        </select>
                    </div>
                    <div class="col-span-2 sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-500">Denumire</label>
                        <input v-model="form.name" type="text" placeholder="Ex: Priza dubla" class="mt-1 w-full rounded-md border-slate-300 text-sm" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>
                    <div class="col-span-1">
                        <label class="block text-xs font-medium text-slate-500">Unitate</label>
                        <select v-model="form.unit" class="mt-1 w-full rounded-md border-slate-300 text-sm">
                            <option v-for="u in units" :key="u" :value="u">{{ u }}</option>
                        </select>
                    </div>
                    <div class="col-span-1">
                        <label class="block text-xs font-medium text-slate-500">Pret (lei)</label>
                        <input v-model.number="form.price" type="number" min="0" step="0.01" class="mt-1 w-full rounded-md border-slate-300 text-sm" />
                        <p v-if="form.errors.price" class="mt-1 text-xs text-red-600">{{ form.errors.price }}</p>
                    </div>
                    <div class="col-span-2 flex items-end sm:col-span-1">
                        <button type="submit" :disabled="form.processing" class="w-full rounded-md bg-brand-navy px-3 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:opacity-50">Adauga</button>
                    </div>
                </form>

                <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-medium uppercase text-slate-500">Tip</th>
                                <th class="px-3 py-3 text-left text-xs font-medium uppercase text-slate-500">Denumire</th>
                                <th class="px-3 py-3 text-left text-xs font-medium uppercase text-slate-500">Unitate</th>
                                <th class="px-3 py-3 text-right text-xs font-medium uppercase text-slate-500">Pret</th>
                                <th class="px-3 py-3 text-center text-xs font-medium uppercase text-slate-500">Activ</th>
                                <th class="px-3 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in priceItems" :key="item.id">
                                <td class="px-3 py-2">
                                    <select v-model="item.object_type" class="rounded-md border-slate-300 text-sm">
                                        <option v-for="key in typeKeys" :key="key" :value="key">{{ typeLabel(key) }}</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <input v-model="item.name" type="text" class="w-full rounded-md border-slate-300 text-sm" />
                                </td>
                                <td class="px-3 py-2">
                                    <select v-model="item.unit" class="rounded-md border-slate-300 text-sm">
                                        <option v-for="u in units" :key="u" :value="u">{{ u }}</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <input v-model.number="item.price" type="number" min="0" step="0.01" class="w-24 rounded-md border-slate-300 text-right text-sm" />
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <input v-model="item.is_active" type="checkbox" class="rounded border-slate-300 text-blue-600" />
                                </td>
                                <td class="px-3 py-2 text-right text-sm">
                                    <button type="button" class="mr-3 font-semibold text-blue-600 hover:text-blue-500" @click="save(item)">Salveaza</button>
                                    <button type="button" class="font-semibold text-red-500 hover:text-red-600" @click="destroy(item)">Sterge</button>
                                </td>
                            </tr>
                            <tr v-if="!priceItems.length">
                                <td colspan="6" class="px-3 py-8 text-center text-sm text-slate-400">Niciun tarif configurat.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
