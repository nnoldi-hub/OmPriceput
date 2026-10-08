<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({ services: Object, filters: Object, trades: Object });

const form = reactive({
    search: props.filters.search ?? '',
    active: props.filters.active ?? '',
    category: props.filters.category ?? '',
});

let timer = null;
watch(form, () => {
    clearTimeout(timer);
    timer = setTimeout(
        () => router.get(route('technical.services.index'), form, { preserveState: true, replace: true }),
        300,
    );
});

function money(value) {
    return Number(value).toLocaleString('ro-RO', { minimumFractionDigits: 2 });
}
</script>

<template>
    <Head title="Catalog servicii" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Catalog servicii</h2>
                <Link :href="route('technical.services.create')" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white">Serviciu nou</Link>
            </div>
        </template>
        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-4 flex flex-wrap gap-3">
                    <input v-model="form.search" type="text" placeholder="Caută serviciu..." class="rounded-md border-slate-300 text-sm shadow-sm" />
                    <select v-model="form.category" class="rounded-md border-slate-300 text-sm shadow-sm">
                        <option value="">Toate categoriile</option>
                        <option v-for="(label, key) in trades" :key="key" :value="key">{{ label }}</option>
                    </select>
                    <select v-model="form.active" class="rounded-md border-slate-300 text-sm shadow-sm">
                        <option value="">Toate</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Serviciu</th>
                                <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Categorie</th>
                                <th class="px-4 py-3 text-right text-xs uppercase text-slate-500">Durată</th>
                                <th class="px-4 py-3 text-right text-xs uppercase text-slate-500">Cost</th>
                                <th class="px-4 py-3 text-right text-xs uppercase text-slate-500">Vânzare</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="service in services.data" :key="service.id" :class="service.is_active ? '' : 'bg-slate-50 text-slate-400'">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">
                                        {{ service.name }}
                                        <span v-if="!service.is_active" class="ml-2 rounded bg-slate-200 px-1.5 py-0.5 text-xs font-normal text-slate-600">inactiv</span>
                                    </div>
                                    <div class="text-xs text-slate-400">{{ service.unit }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ trades[service.category] ?? service.category }}</td>
                                <td class="px-4 py-3 text-right text-sm">{{ service.duration_minutes }} min</td>
                                <td class="px-4 py-3 text-right text-sm">{{ money(service.cost_price) }} lei</td>
                                <td class="px-4 py-3 text-right text-sm">{{ money(service.sale_price) }} lei</td>
                                <td class="px-4 py-3 text-right"><Link :href="route('technical.services.edit', service.id)" class="text-blue-600">Editează</Link></td>
                            </tr>
                            <tr v-if="!services.data.length">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">Niciun serviciu.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="services.links && services.last_page > 1" class="mt-4 flex flex-wrap items-center gap-1">
                    <template v-for="(link, i) in services.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            class="rounded-md border px-3 py-1.5 text-sm"
                            :class="link.active ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                            v-html="link.label"
                        />
                        <span v-else class="rounded-md border border-slate-100 px-3 py-1.5 text-sm text-slate-300" v-html="link.label" />
                    </template>
                    <span class="ml-2 text-xs text-slate-500">{{ services.total }} servicii</span>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>