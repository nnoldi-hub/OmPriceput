<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

defineProps({ packages: Array });
const page = usePage();

function destroy(pkg) {
    if (confirm(`Stergi pachetul "${pkg.name}"?`)) {
        router.delete(route('admin.site-packages.destroy', pkg.id));
    }
}
</script>

<template>
    <Head title="Pachete site" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Pachete site</h2>
                <Link :href="route('admin.site-packages.create')" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500">Pachet nou</Link>
            </div>
        </template>
        <div class="py-8">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <div v-if="page.props.flash.success" class="mb-4 rounded-md bg-green-50 p-4 text-sm font-medium text-green-800">{{ page.props.flash.success }}</div>
                <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50"><tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Pachet</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Pret pe an</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Detalii</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="pkg in packages" :key="pkg.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3"><div class="font-medium text-slate-900">{{ pkg.name }}</div><div class="text-xs text-slate-500">{{ pkg.key }}</div></td>
                                <td class="px-4 py-3 text-right text-sm">{{ Number(pkg.price_from).toLocaleString('ro-RO') }} lei</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ pkg.vizite_an }} {{ pkg.vizite_an === 1 ? 'vizita' : 'vizite' }}/an &middot; {{ pkg.timp_raspuns || '-' }} &middot; {{ pkg.prioritate || '-' }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs font-medium" :class="pkg.active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-500'">{{ pkg.active ? 'Activ' : 'Ascuns' }}</span></td>
                                <td class="px-4 py-3 text-right text-sm"><Link :href="route('admin.site-packages.edit', pkg.id)" class="text-slate-500 hover:text-slate-700">Editeaza</Link><button class="ml-3 text-red-500 hover:text-red-700" @click="destroy(pkg)">Sterge</button></td>
                            </tr>
                            <tr v-if="!packages.length"><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">Niciun pachet.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
