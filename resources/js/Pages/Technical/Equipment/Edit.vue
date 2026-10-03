<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    equipment: Object,
    suppliers: Array,
    categories: Object,
    units: Array,
});

const form = useForm({
    name: props.equipment.name,
    category: props.equipment.category,
    sku: props.equipment.sku,
    unit: props.equipment.unit,
    unit_price: Number(props.equipment.unit_price),
    cost_price: Number(props.equipment.cost_price ?? 0),
    markup_percent: Number(props.equipment.markup_percent ?? 0),
    supplier_id: props.equipment.supplier_id ?? '',
    stock_quantity: props.equipment.stock_quantity,
    minimum_stock: props.equipment.minimum_stock ?? 5,
    description: props.equipment.description,
    is_active: props.equipment.is_active ?? true,
    is_visible_in_shop: props.equipment.is_visible_in_shop ?? false,
    shop_description: props.equipment.shop_description ?? '',
    image: null,
});

function submit() {
    form.put(route('technical.equipment.update', props.equipment.id));
}
</script>

<template>
    <Head title="Editeaza echipament" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Editeaza echipament</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <form class="space-y-6 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Nume *</label>
                            <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Categorie</label>
                            <select v-model="form.category" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option v-for="(label, value) in categories" :key="value" :value="value">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Furnizor</label>
                            <select v-model="form.supplier_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                                <option value="">Fara furnizor</option>
                                <option v-for="supplier in props.suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Adaos (%)</label>
                            <input v-model.number="form.markup_percent" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">SKU</label>
                            <input v-model="form.sku" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Unitate</label>
                            <input v-model="form.unit" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Pret unitar (lei)</label>
                            <input v-model.number="form.unit_price" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Pret cost (lei)</label>
                            <input v-model.number="form.cost_price" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Stoc</label>
                            <input v-model.number="form.stock_quantity" type="number" min="0" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Prag minim stoc</label>
                            <input v-model.number="form.minimum_stock" type="number" min="0" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                            <p class="mt-1 text-xs text-slate-500">Prag pentru recomandarea de reaprovizionare.</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Descriere</label>
                            <textarea v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <label class="flex items-center gap-2 text-sm text-slate-600 sm:col-span-2">
                            <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                            Material activ in catalog
                        </label>
                        <div class="sm:col-span-2 border-t pt-4">
                            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                <input v-model="form.is_visible_in_shop" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                Vizibil in magazinul online
                            </label>
                            <p class="mt-1 text-xs text-slate-500">Produsul va aparea public in magazin, la pretul de vanzare (unitar).</p>
                            <p v-if="equipment.slug" class="mt-1 text-xs text-slate-400">URL magazin: /magazin/{{ equipment.slug }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Descriere pentru magazin</label>
                            <textarea v-model="form.shop_description" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" placeholder="Descriere afisata clientilor in magazin (optional, altfel se foloseste descrierea interna)" />
                        </div>
                        <div class="sm:col-span-2">
                            <img v-if="equipment.image_path" :src="`/storage/${equipment.image_path}`" alt="" class="mb-2 h-24 w-24 rounded-md object-contain" />
                            <label class="block text-sm font-medium text-slate-700">Imagine produs</label>
                            <input type="file" accept="image/*" class="mt-1 block w-full text-sm" @input="form.image = $event.target.files[0]" />
                            <p v-if="form.errors.image" class="mt-1 text-sm text-red-600">{{ form.errors.image }}</p>
                        </div>
                    </div>
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
