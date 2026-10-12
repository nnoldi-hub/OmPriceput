<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const query = ref('');
const results = ref({ clients: [], offers: [], tickets: [], agenda_items: [], projects: [] });
const open = ref(false);
const loading = ref(false);
let debounceTimer = null;

const hasResults = () =>
    results.value.clients.length ||
    results.value.offers.length ||
    results.value.tickets.length ||
    results.value.agenda_items.length ||
    results.value.projects.length;

const agendaTypeLabels = { task: 'Task', nota: 'Nota', idee: 'Idee', obiectiv: 'Obiectiv', masuratoare: 'Masuratoare' };

watch(query, (value) => {
    clearTimeout(debounceTimer);

    if (value.trim().length < 2) {
        results.value = { clients: [], offers: [], tickets: [], agenda_items: [], projects: [] };
        open.value = false;
        return;
    }

    debounceTimer = setTimeout(async () => {
        loading.value = true;
        try {
            const response = await fetch(route('search', { q: value }), {
                headers: { Accept: 'application/json' },
            });
            results.value = await response.json();
            open.value = true;
        } finally {
            loading.value = false;
        }
    }, 250);
});

function close() {
    setTimeout(() => { open.value = false; }, 150);
}

const offerStatusClasses = {
    draft: 'bg-slate-100 text-slate-600',
    sent: 'bg-blue-100 text-blue-700',
    accepted: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-700',
    expired: 'bg-slate-100 text-slate-500',
};

function agendaItemHref(item) {
    return item.project_id ? route('agenda.projects.show', item.project_id) : route('agenda.inbox');
}
</script>

<template>
    <div class="relative w-full max-w-xs">
        <input
            v-model="query"
            type="text"
            placeholder="Cauta clienti, oferte, tichete, agenda..."
            class="w-full rounded-md border-slate-300 py-1.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
            @focus="query.length >= 2 && (open = true)"
            @blur="close"
        />

        <div
            v-if="open"
            class="absolute z-50 mt-1 w-96 max-w-[90vw] rounded-md border border-slate-200 bg-white shadow-lg"
        >
            <div v-if="loading" class="p-4 text-sm text-slate-400">Se cauta...</div>
            <div v-else-if="!hasResults()" class="p-4 text-sm text-slate-400">Niciun rezultat.</div>
            <div v-else class="max-h-96 overflow-y-auto py-2">
                <div v-if="results.clients.length">
                    <div class="px-3 py-1 text-xs font-semibold uppercase text-slate-400">Clienti</div>
                    <Link
                        v-for="client in results.clients"
                        :key="'c'+client.id"
                        :href="route('sales.clients.show', client.id)"
                        class="block px-3 py-2 text-sm hover:bg-slate-50"
                    >
                        <span class="font-medium text-slate-900">{{ client.name }}</span>
                        <span class="ml-2 text-xs text-slate-400">{{ client.phone }}</span>
                    </Link>
                </div>
                <div v-if="results.offers.length">
                    <div class="px-3 py-1 text-xs font-semibold uppercase text-slate-400">Oferte</div>
                    <Link
                        v-for="offer in results.offers"
                        :key="'o'+offer.id"
                        :href="route('sales.offers.show', offer.id)"
                        class="flex items-center justify-between px-3 py-2 text-sm hover:bg-slate-50"
                    >
                        <span class="text-slate-900">{{ offer.title }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="offerStatusClasses[offer.status]">{{ offer.status }}</span>
                    </Link>
                </div>
                <div v-if="results.tickets.length">
                    <div class="px-3 py-1 text-xs font-semibold uppercase text-slate-400">Tichete</div>
                    <Link
                        v-for="ticket in results.tickets"
                        :key="'t'+ticket.id"
                        :href="route('technical.tickets.show', ticket.id)"
                        class="block px-3 py-2 text-sm hover:bg-slate-50"
                    >
                        <span class="font-medium text-slate-900">{{ ticket.subject }}</span>
                        <span class="ml-2 text-xs text-slate-400">{{ ticket.client.name }}</span>
                    </Link>
                </div>
                <div v-if="results.agenda_items.length">
                    <div class="px-3 py-1 text-xs font-semibold uppercase text-slate-400">Agenda</div>
                    <Link
                        v-for="item in results.agenda_items"
                        :key="'a'+item.id"
                        :href="agendaItemHref(item)"
                        class="block px-3 py-2 text-sm hover:bg-slate-50"
                    >
                        <span class="font-medium text-slate-900">{{ item.title }}</span>
                        <span class="ml-2 text-xs text-slate-400">{{ agendaTypeLabels[item.type] ?? item.type }}</span>
                        <span v-if="item.project" class="ml-2 text-xs text-slate-400">→ {{ item.project.name }}</span>
                    </Link>
                </div>
                <div v-if="results.projects.length">
                    <div class="px-3 py-1 text-xs font-semibold uppercase text-slate-400">Proiecte</div>
                    <Link
                        v-for="project in results.projects"
                        :key="'p'+project.id"
                        :href="route('agenda.projects.show', project.id)"
                        class="block px-3 py-2 text-sm hover:bg-slate-50"
                    >
                        <span class="font-medium text-slate-900">{{ project.name }}</span>
                        <span v-if="project.client" class="ml-2 text-xs text-slate-400">{{ project.client.name }}</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
