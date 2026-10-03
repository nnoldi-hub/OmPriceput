<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const { canManage } = usePermissions();

const props = defineProps({
    clients: Object,
    pipeline: Array,
    filters: Object,
});
import { ref } from 'vue';
const viewMode = ref('list');
const stages = [
    { key: 'new', label: 'Lead nou' },
    { key: 'contacted', label: 'Contactat' },
    { key: 'visit', label: 'Vizita' },
    { key: 'proposal', label: 'Oferta' },
    { key: 'won', label: 'Castigat' },
    { key: 'lost', label: 'Pierdut' },
];
const stageLabels = Object.fromEntries(stages.map((stage) => [stage.key, stage.label]));
function stageClients(stage) { return props.pipeline.filter((client) => client.pipeline_stage === stage); }
function updateStage(client, event) {
    const data = { pipeline_stage: event.target.value, lost_reason: event.target.value === 'lost' ? client.lost_reason : null };
    router.patch(route('sales.clients.pipeline', client.id), data, { preserveScroll: true });
}

const form = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    source: props.filters.source ?? '',
});

let debounceTimer = null;
watch(form, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('sales.clients.index'), form, { preserveState: true, replace: true });
    }, 300);
});

const statusLabels = { lead: 'Lead', client: 'Client', inactive: 'Inactiv' };
const statusClasses = {
    lead: 'bg-amber-100 text-amber-800',
    client: 'bg-green-100 text-green-800',
    inactive: 'bg-slate-100 text-slate-600',
};

function exportUrl() {
    const params = new URLSearchParams(form).toString();
    return route('sales.clients.export') + (params ? `?${params}` : '');
}

function destroy(client) {
    if (confirm(`Stergi clientul "${client.name}"? Aceasta actiune este ireversibila.`)) {
        router.delete(route('sales.clients.destroy', client.id));
    }
}
</script>

<template>
    <Head title="Clienti" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Clienti</h2>
                <div class="flex gap-2">
                    <a :href="exportUrl()" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Export Excel
                    </a>
                    <Link v-if="canManage('clients')" :href="route('sales.clients.create')" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                        Client nou
                    </Link>
                </div>

            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <input
                        v-model="form.search"
                        type="text"
                        placeholder="Cauta dupa nume, telefon, email..."
                        class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <select v-model="form.status" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Toate statusurile</option>
                        <option value="lead">Lead</option>
                        <option value="client">Client</option>
                        <option value="inactive">Inactiv</option>
                    </select>
                    <select v-model="form.source" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Toate sursele</option>
                        <option value="web">Web</option>
                        <option value="phone">Telefon</option>
                        <option value="referral">Recomandare</option>
                        <option value="manual">Manual</option>
                    </select>
                    <div class="ml-auto flex rounded-md border border-slate-300 bg-white p-1">
                        <button type="button" class="rounded px-3 py-1 text-sm" :class="viewMode === 'list' ? 'bg-slate-900 text-white' : 'text-slate-600'" @click="viewMode = 'list'">Lista</button>
                        <button type="button" class="rounded px-3 py-1 text-sm" :class="viewMode === 'pipeline' ? 'bg-slate-900 text-white' : 'text-slate-600'" @click="viewMode = 'pipeline'">Pipeline</button>
                    </div>
                </div>

                <div v-if="viewMode === 'list'" class="rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Nume</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Contact</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Sursa</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Asignat</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="client in clients.data" :key="client.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <Link :href="route('sales.clients.show', client.id)" class="font-medium text-blue-600 hover:text-blue-500">
                                        {{ client.name }}
                                    </Link>
                                    <div v-if="client.company_name" class="text-xs text-slate-400">{{ client.company_name }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <div>{{ client.phone }}</div>
                                    <div class="text-xs text-slate-400">{{ client.email }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm capitalize text-slate-600">{{ client.source }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses[client.status]">
                                        {{ statusLabels[client.status] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ client.assigned_to?.name ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <template v-if="canManage('clients')">
                                        <Link :href="route('sales.clients.edit', client.id)" class="text-slate-500 hover:text-slate-700">Editeaza</Link>
                                        <button class="ml-3 text-red-500 hover:text-red-700" @click="destroy(client)">Sterge</button>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="!clients.data.length">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">Niciun client gasit.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                </div>

                <div v-else class="grid grid-cols-1 gap-4 overflow-x-auto lg:grid-cols-6">
                    <div v-for="stage in stages" :key="stage.key" class="min-w-52 rounded-lg bg-slate-100 p-3">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-700">{{ stage.label }}</h3>
                            <span class="rounded-full bg-white px-2 py-0.5 text-xs text-slate-500">{{ stageClients(stage.key).length }}</span>
                        </div>
                        <div class="space-y-3">
                            <div v-for="client in stageClients(stage.key)" :key="client.id" class="rounded-md bg-white p-3 shadow-sm">
                                <Link :href="route('sales.clients.show', client.id)" class="font-medium text-blue-600 hover:text-blue-500">{{ client.name }}</Link>
                                <div v-if="client.company_name" class="mt-1 text-xs text-slate-400">{{ client.company_name }}</div>
                                <select v-if="canManage('clients')" :value="client.pipeline_stage" class="mt-3 w-full rounded border-slate-300 text-xs" @change="updateStage(client, $event)">
                                    <option v-for="option in stages" :key="option.key" :value="option.key">{{ option.label }}</option>
                                </select>
                                <p v-else class="mt-3 text-xs font-medium text-slate-500">{{ stageLabels[client.pipeline_stage] }}</p>
                                <div v-if="client.pipeline_stage === 'lost' && client.lost_reason" class="mt-2 text-xs text-red-600">{{ client.lost_reason }}</div>
                            </div>
                            <div v-if="!stageClients(stage.key).length" class="py-5 text-center text-xs text-slate-400">Niciun lead</div>
                        </div>
                    </div>
                </div>

                <div v-if="clients.links.length > 3" class="mt-4 flex flex-wrap gap-2">
                    <Link
                        v-for="link in clients.links"
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
