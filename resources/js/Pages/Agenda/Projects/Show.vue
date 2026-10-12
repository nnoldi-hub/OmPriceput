<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ project: Object, items: Array, progress: Number, clients: Array });
const page = usePage();

const typeLabels = { task: 'Task', nota: 'Nota', idee: 'Idee', obiectiv: 'Obiectiv', masuratoare: 'Masuratoare' };
const projectTypeLabels = { lucrare: 'Lucrare', propriu: 'Propriu' };
const projectStatusLabels = { activ: 'Activ', in_pauza: 'In pauza', incheiat: 'Incheiat' };
const projectStatusClasses = { activ: 'bg-green-100 text-green-700', in_pauza: 'bg-amber-100 text-amber-700', incheiat: 'bg-slate-100 text-slate-500' };

const showEdit = ref(false);
const edit = ref({
    name: props.project.name,
    type: props.project.type,
    status: props.project.status,
    client_id: props.project.client_id ?? null,
    description: props.project.description ?? '',
});

const capture = ref('');
const captureType = ref('task');
const captureInput = ref(null);

const doneItems = props.items.filter((i) => i.status === 'gata');
const openItems = props.items.filter((i) => i.status !== 'gata');

function submitEdit() {
    router.patch(route('agenda.projects.update', props.project.id), edit.value, {
        preserveScroll: true,
        onSuccess: () => { showEdit.value = false; },
    });
}

function addItem() {
    const title = capture.value.trim();
    if (!title) return;
    router.post(route('agenda.items.store'), {
        title,
        type: captureType.value,
        status: 'de_facut',
        project_id: props.project.id,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            capture.value = '';
            captureType.value = 'task';
            captureInput.value?.focus();
        },
    });
}

function toggle(item) {
    router.patch(route('agenda.items.toggle', item.id), {}, { preserveScroll: true });
}

function remove(item) {
    if (confirm(`Stergi "${item.title}"?`)) router.delete(route('agenda.items.destroy', item.id), { preserveScroll: true });
}

function destroyProject() {
    if (confirm(`Inchizi definitiv proiectul "${props.project.name}"? Se sterge si continutul lui.`)) {
        router.delete(route('agenda.projects.destroy', props.project.id));
    }
}

function dueLabel(value) {
    if (!value) return '';
    return new Date(value).toLocaleDateString('ro-RO', { day: 'numeric', month: 'short' });
}
</script>

<template>
    <Head :title="project.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <Link :href="route('agenda.projects.index')" class="text-sm text-slate-400 hover:text-slate-600">← Proiecte</Link>
                    <h2 class="mt-1 truncate text-xl font-semibold leading-tight text-gray-800">{{ project.name }}</h2>
                </div>
                <button type="button" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-600 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50" @click="showEdit = !showEdit">
                    {{ showEdit ? 'Anuleaza' : 'Editeaza' }}
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div v-if="page.props.flash.success" class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ page.props.flash.success }}</div>

                <form v-if="showEdit" class="mb-6 space-y-4 rounded-xl bg-white p-5 shadow-sm" @submit.prevent="submitEdit">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Editeaza proiectul</h3>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Nume *</label>
                        <input v-model="edit.name" type="text" required class="mt-1 block w-full rounded-md border-slate-300" />
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Tip</label>
                            <select v-model="edit.type" class="mt-1 block w-full rounded-md border-slate-300">
                                <option value="propriu">Propriu</option>
                                <option value="lucrare">Lucrare</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Status</label>
                            <select v-model="edit.status" class="mt-1 block w-full rounded-md border-slate-300">
                                <option value="activ">Activ</option>
                                <option value="in_pauza">In pauza</option>
                                <option value="incheiat">Incheiat</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Client (optional)</label>
                        <select v-model="edit.client_id" class="mt-1 block w-full rounded-md border-slate-300">
                            <option :value="null">Fara client</option>
                            <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Descriere</label>
                        <textarea v-model="edit.description" rows="3" class="mt-1 block w-full rounded-md border-slate-300" />
                    </div>
                    <button type="submit" class="rounded-md bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-500">Salveaza</button>
                </form>

                <div class="mb-6 rounded-xl bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                        <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ projectTypeLabels[project.type] }}</span>
                        <span v-if="project.client" class="font-medium text-blue-600">{{ project.client.name }}</span>
                        <span class="rounded-full px-2 py-0.5 font-medium" :class="projectStatusClasses[project.status]">{{ projectStatusLabels[project.status] }}</span>
                        <span class="ml-auto">{{ openItems.length }} deschise · {{ doneItems.length }} gata</span>
                    </div>
                    <div v-if="project.description" class="mt-2 text-sm text-slate-500">{{ project.description }}</div>
                    <div class="mt-3 flex items-center gap-3">
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-green-500" :style="{ width: progress + '%' }" />
                        </div>
                        <span class="text-sm font-semibold text-slate-600">{{ progress }}%</span>
                    </div>
                </div>

                <form @submit.prevent="addItem">
                    <div class="flex gap-2">
                        <input
                            ref="captureInput"
                            v-model="capture"
                            type="text"
                            autocomplete="off"
                            placeholder="Adauga task, nota sau idee in proiect..."
                            class="w-full rounded-2xl border-slate-300 px-5 py-4 text-lg shadow-sm outline-none focus:border-brand-navy focus:ring-brand-navy"
                            enterkeyhint="send"
                        />
                        <select v-model="captureType" class="flex-shrink-0 rounded-2xl border-slate-300 bg-white px-3 text-sm shadow-sm">
                            <option value="task">Task</option>
                            <option value="nota">Nota</option>
                            <option value="idee">Idee</option>
                        </select>
                    </div>
                </form>

                <section class="mt-6">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Deschise ({{ openItems.length }})</h3>
                    <div v-if="!openItems.length" class="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-center text-sm text-slate-400">Nimic deschis in proiect.</div>
                    <div v-for="item in openItems" :key="item.id" class="mb-2 rounded-xl bg-white p-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <button type="button" class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border-2 border-slate-300 text-transparent transition hover:border-blue-400 hover:text-blue-500" @click="toggle(item)">✓</button>
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-slate-900">{{ item.title }}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ typeLabels[item.type] }}</span>
                                    <span v-if="item.due_at" class="text-slate-400">Termen: {{ dueLabel(item.due_at) }}</span>
                                    <span v-if="item.repeat" class="rounded-full bg-purple-100 px-2 py-0.5 text-purple-700">repetabil</span>
                                </div>
                            </div>
                            <button type="button" class="text-sm font-semibold text-red-400 hover:text-red-600" @click="remove(item)">Sterge</button>
                        </div>
                    </div>
                </section>

                <details class="mt-6 rounded-xl bg-white p-4 shadow-sm">
                    <summary class="cursor-pointer text-sm font-semibold uppercase tracking-wide text-slate-500">Gata ({{ doneItems.length }})</summary>
                    <div class="mt-3 space-y-2">
                        <div v-for="item in doneItems" :key="item.id" class="flex items-center gap-3 border-t border-slate-100 pt-2">
                            <button type="button" class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border-2 border-green-500 bg-green-500 text-white" @click="toggle(item)">✓</button>
                            <div class="min-w-0 flex-1">
                                <span class="text-slate-400 line-through">{{ item.title }}</span>
                            </div>
                            <button type="button" class="text-sm font-semibold text-red-400 hover:text-red-600" @click="remove(item)">Sterge</button>
                        </div>
                    </div>
                </details>

                <div class="mt-8 text-right">
                    <button type="button" class="text-sm font-semibold text-red-500 hover:text-red-700" @click="destroyProject">Sterge proiectul</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>