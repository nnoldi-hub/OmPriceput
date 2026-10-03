<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ discounts: Object, categories: Object });

const categoryLabels = props.categories ?? {};

function destroy(discount) {
    if (confirm(`Stergi reducerea "${discount.name}"?`)) {
        router.delete(route('admin.discounts.destroy', discount.id));
    }
}
</script>

<template>
    <Head title="Reduceri magazin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Reduceri magazin</h2>
                <Link :href="route('admin.discounts.create')" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500">Reducere noua</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Nume</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Aplicabil la</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Valoare</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Perioada</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="discount in discounts.data" :key="discount.id">
                                <td class="px-4 py-3 font-medium text-slate-800">{{ discount.name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    {{ discount.scope === 'product' ? (discount.equipment?.name ?? '-') : `Categorie: ${categoryLabels[discount.category] ?? discount.category}` }}
                                </td>
                                <td class="px-4 py-3 text-right">{{ discount.type === 'percent' ? `${Number(discount.value)}%` : `${Number(discount.value)} lei` }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <span v-if="!discount.starts_at && !discount.ends_at">Permanent</span>
                                    <span v-else>{{ discount.starts_at ? new Date(discount.starts_at).toLocaleDateString('ro-RO') : '...' }} - {{ discount.ends_at ? new Date(discount.ends_at).toLocaleDateString('ro-RO') : '...' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="discount.is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-500'">{{ discount.is_active ? 'Activa' : 'Inactiva' }}</span>
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <Link :href="route('admin.discounts.edit', discount.id)" class="mr-3 text-blue-600 hover:text-blue-700">Editeaza</Link>
                                    <button class="text-red-600 hover:text-red-700" @click="destroy(discount)">Sterge</button>
                                </td>
                            </tr>
                            <tr v-if="!discounts.data.length">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">Nicio reducere configurata.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="discounts.links.length > 3" class="mt-4 flex flex-wrap gap-2">
                    <Link
                        v-for="link in discounts.links"
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
