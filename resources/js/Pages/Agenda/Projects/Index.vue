<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ projects: Array, clients: Array });
const page = usePage();

const typeLabels = { lucrare: 'Lucrare', propriu: 'Propriu' };
const statusLabels = { activ: 'Activ', in_pauza: 'In pauza', incheiat: 'Incheiat' };
const statusClasses = { activ: 'bg-green-100 text-green-700', in_pauza: 'bg-amber-100 text-amber-700', incheiat: 'bg-slate-100 text-slate-500' };

const showForm = ref(false);
const form = {
    name: '',
    type: 'propriu',
    status: 'activ',
    client_id: null,
    description: '',
};

function submit() {
    router.post(route('agenda.projects.store'), form, {
        onSuccess: () => {
            showForm.value = false;
            form.name = '';
            form.type = 'propriu';
            form.status = 'activ';
            form.client_id = null;
            form.description = '';
        },
    });
}

function progress(project) {
    if (!project.total_items_count) return 0;
    return Math.round((project.done_items_count / project.total_items_count) * 100);
}
</script>

<template>
    <Head title="Proiecte" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Proiecte</h2>
                <button
                    type="button"
                    class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500"
                    @click="showForm = !showForm"
                >
                    {{ showForm ? 'Anuleaza' : 'Proiect nou' }}
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div v-if="page.props.flash.success" class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ page.props.flash.success }}</div>

                <form v-if="showForm" class="mb-6 space-y-4 rounded-xl bg-white p-5 shadow-sm" @submit.prevent="submit">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Proiect nou</h3>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Nume *</label>
                        <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-md border-slate-300" placeholder="De exemplu: Montaj camera living" />
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Tip</label>
                            <select v-model="form.type" class="mt-1 block w-full rounded-md border-slate-300">
                                <option value="propriu">Propriu</option>
                                <option value="lucrare">Lucrare</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Status</label>
                            <select v-model="form.status" class="mt-1 block w-full rounded-md border-slate-300">
                                <option value="activ">Activ</option>
                                <option value="in_pauza">In pauza</option>
                                <option value="incheiat">Incheiat</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Client (optional)</label>
                        <select v-model="form.client_id" class="mt-1 block w-full rounded-md border-slate-300">
                            <option :value="null">Fara client</option>
                            <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Descriere</label>
                        <textarea v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-slate-300" placeholder="Despre ce e proiectul..." />
                    </div>
                    <button type="submit" class="w-full rounded-md bg-blue-600 py-3 text-sm font-semibold text-white hover:bg-blue-500 sm:w-auto sm:px-6">Creeaza proiectul</button>
                </form>

                <div v-if="!projects.length && !showForm" class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-400">
                    Niciun proiect inca. Creeaza primul proiect sau lasa-l pe aplicatie sa porneasca de la zero: apasa „Proiect nou".
                </div>

                <Link
                    v-for="project in projects"
                    :key="project.id"
                    :href="route('agenda.projects.show', project.id)"
                    class="mb-3 block rounded-xl bg-white p-4 shadow-sm transition hover:bg-slate-50"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-medium text-slate-900">{{ project.name }}</div>
                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ typeLabels[project.type] }}</span>
                                <span v-if="project.client" class="text-blue-600">{{ project.client.name }}</span>
                                <span class="rounded-full px-2 py-0.5 font-medium" :class="statusClasses[project.status]">{{ statusLabels[project.status] }}</span>
                            </div>
                            <div v-if="project.description" class="mt-1 line-clamp-2 text-xs text-slate-400">{{ project.description }}</div>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <div class="text-lg font-semibold text-slate-900">{{ project.open_items_count }}</div>
                            <div class="text-xs text-slate-400">deschise</div>
                        </div>
                    </div>
                    <div v-if="project.total_items_count" class="mt-3">
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-green-500" :style="{ width: progress(project) + '%' }" />
                        </div>
                        <div class="mt-1 text-right text-xs text-slate-400">{{ progress(project) }}% · {{ project.done_items_count }}/{{ project.total_items_count }} gata</div>
                    </div>
                    <div v-else class="mt-3 text-right text-xs text-slate-400">Fara iteme inca</div>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>