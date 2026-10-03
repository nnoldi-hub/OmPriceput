<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({
    equipment: Object,
    filters: Object,
    categories: Object,
});

const form = reactive({
    search: props.filters.search ?? '',
    category: props.filters.category ?? '',
    low_stock: props.filters.low_stock ?? false,
});

let debounceTimer = null;
watch(form, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('technical.equipment.index'), form, { preserveState: true, replace: true });
    }, 300);
});

function money(value) {
    return Number(value).toLocaleString('ro-RO', { minimumFractionDigits: 2 });
}

function adjustStock(item, delta) {
    router.patch(route('technical.equipment.stock', item.id), { delta }, { preserveScroll: true, preserveState: true });
}

function destroy(item) {
    if (confirm(`Stergi echipamentul "${item.name}"?`)) {
        router.delete(route('technical.equipment.destroy', item.id));
    }

    function generateReplenishmentOrders() {
        if (confirm('Generezi comenzi recomandate pentru toate materialele sub pragul minim?')) {
            router.post(route('technical.purchase-orders.replenish'), {}, { preserveScroll: true });
        }
    }
}
</script>

<template>
    <Head title="Echipamente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Echipamente</h2>
                <Link :href="route('technical.equipment.create')" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                    Echipament nou
                </Link>
                <Link :href="route('technical.suppliers.index')" class="ml-2 rounded-md bg-slate-700 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-600">
                    Furnizori / Import
                </Link>
                <button type="button" class="ml-2 rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-500" @click="generateReplenishmentOrders">
                    Genereaza comenzi recomandate
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
                    <input v-model="form.search" type="text" placeholder="Cauta dupa nume sau SKU..." class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    <select v-model="form.category" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Toate categoriile</option>
                        <option v-for="(label, value) in categories" :key="value" :value="value">{{ label }}</option>
                    </select>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input v-model="form.low_stock" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                        Doar stoc sub pragul minim
                    </label>
                </div>

                <div class="rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Nume</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Categorie</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Pret vanzare / cost</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase text-slate-500">Stoc</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase text-slate-500">Reaprovizionare</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in equipment.data" :key="item.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ item.name }}</div>
                                    <div class="text-xs text-slate-400">{{ item.sku }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm capitalize text-slate-600">{{ item.category }}</td>
                                <td class="px-4 py-3 text-right text-sm text-slate-900">
                                    {{ money(item.unit_price) }} / {{ money(item.cost_price) }} lei
                                    <div class="text-xs text-emerald-600">Adaos {{ money(item.markup_percent) }}%</div>
                                    <div v-if="item.supplier" class="text-xs text-slate-400">{{ item.supplier.name }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <button class="rounded border border-slate-300 px-2 text-slate-500 hover:bg-slate-100" @click="adjustStock(item, -1)">-</button>
                                        <span class="w-10 text-center text-sm font-medium" :class="item.stock_quantity <= item.minimum_stock ? 'text-red-600' : 'text-slate-900'">
                                            {{ item.stock_quantity }}
                                        </span>
                                        <button class="rounded border border-slate-300 px-2 text-slate-500 hover:bg-slate-100" @click="adjustStock(item, 1)">+</button>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <span v-if="item.stock_quantity <= item.minimum_stock" class="rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700">
                                        Comanda {{ Math.max(item.minimum_stock * 2 - item.stock_quantity, 0) }} {{ item.unit }}
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <span class="mr-3 rounded-full px-2 py-1 text-xs" :class="item.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'">{{ item.is_active ? 'Activ' : 'Inactiv' }}</span>
                                    <Link :href="route('technical.equipment.edit', item.id)" class="text-slate-500 hover:text-slate-700">Editeaza</Link>
                                    <button class="ml-3 text-red-500 hover:text-red-700" @click="destroy(item)">Sterge</button>
                                </td>
                            </tr>
                            <tr v-if="!equipment.data.length">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">Niciun echipament gasit.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                </div>

                <div v-if="equipment.links.length > 3" class="mt-4 flex flex-wrap gap-2">
                    <Link
                        v-for="link in equipment.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        :class="[
                            'rounded-md px-3 py-1.5 text-sm',
                            link.active ? 'bg-blue-600 text-white' : 'bg-white text-slate-500 hover:bg-slate-100',
                            !link.url ? 'pointer-events-none opacity-40' : '',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
