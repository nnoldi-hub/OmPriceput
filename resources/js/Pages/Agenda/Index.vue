<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';

const props = defineProps({ items: Array, projects: Array });
const page = usePage();

const typeLabels = { task: 'Task', nota: 'Nota', idee: 'Idee', obiectiv: 'Obiectiv', masuratoare: 'Masuratoare' };
const priorityClasses = { low: 'bg-slate-100 text-slate-600', normal: 'bg-blue-100 text-blue-700', high: 'bg-red-100 text-red-700' };
const priorityLabels = { low: 'Scazuta', normal: 'Normala', high: 'Ridicata' };

const capture = ref('');
const captureInput = ref(null);

const inboxItems = computed(() => props.items.filter((i) => i.status === 'inbox'));
const openItems = computed(() => props.items.filter((i) => i.status === 'de_facut' || i.status === 'in_lucru'));
const doneItems = computed(() => props.items.filter((i) => i.status === 'gata'));

function add() {
    const title = capture.value.trim();
    if (!title) return;
    router.post(route('agenda.items.store'), { title }, {
        preserveScroll: true,
        onSuccess: () => { capture.value = ''; refocus(); },
    });
}

function patch(item, overrides = {}) {
    router.patch(route('agenda.items.update', item.id), {
        title: item.title,
        text: item.text,
        type: item.type,
        status: item.status,
        priority: item.priority,
        project_id: item.project_id ?? null,
        due_at: item.due_at ? String(item.due_at).slice(0, 10) : null,
        ...overrides,
    }, { preserveScroll: true });
}

function toggleDone(item) {
    patch(item, { status: item.status === 'gata' ? 'de_facut' : 'gata' });
}

function moveToDo(item) {
    patch(item, { status: 'de_facut' });
}

function remove(item) {
    if (confirm(`Stergi "${item.title}"?`)) router.delete(route('agenda.items.destroy', item.id), { preserveScroll: true });
}

function formatDate(value) {
    if (!value) return null;
    return new Date(value).toLocaleDateString('ro-RO', { day: 'numeric', month: 'short', year: 'numeric' });
}

function refocus() {
    nextTick(() => captureInput.value?.focus());
}

onMounted(refocus);
</script>

<template>
    <Head title="Agenda" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Agenda</h2>
                <span class="hidden text-sm text-slate-400 sm:block">Scrie si apasa Enter — totul intra in Inbox</span>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div v-if="page.props.flash.success" class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ page.props.flash.success }}</div>

                <form @submit.prevent="add">
                    <input
                        ref="captureInput"
                        v-model="capture"
                        type="text"
                        autocomplete="off"
                        placeholder="Scrie aici ce ai de facut..."
                        class="w-full rounded-2xl border-slate-300 px-5 py-4 text-lg shadow-sm outline-none focus:border-brand-navy focus:ring-brand-navy"
                        enterkeyhint="send"
                    />
                </form>

                <section class="mt-8">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Inbox ({{ inboxItems.length }})</h3>
                    <div v-if="!inboxItems.length" class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-400">
                        Inbox-ul e gol. Tasteaza deasupra orice idee, nota sau task.
                    </div>
                    <div v-for="item in inboxItems" :key="item.id" class="mb-3 rounded-xl bg-white p-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-slate-900">{{ item.title }}</div>
                                <div v-if="item.text" class="mt-1 whitespace-pre-line text-sm text-slate-500">{{ item.text }}</div>
                            </div>
                            <button type="button" class="text-sm font-semibold text-blue-600 hover:text-blue-500" @click="moveToDo(item)">De facut</button>
                            <button type="button" class="text-sm font-semibold text-red-400 hover:text-red-600" @click="remove(item)">Sterge</button>
                        </div>
                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <select v-model="item.type" class="rounded-md border-slate-300 text-sm" @change="patch(item)">
                                <option v-for="(label, value) in typeLabels" :key="value" :value="value">{{ label }}</option>
                            </select>
                            <select v-model.number="item.project_id" class="rounded-md border-slate-300 text-sm" @change="patch(item)">
                                <option :value="null">Fara proiect</option>
                                <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
                            </select>
                            <input v-model="item.due_at" type="date" class="rounded-md border-slate-300 text-sm" @change="patch(item)" />
                        </div>
                    </div>
                </section>

                <section class="mt-8">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">De facut ({{ openItems.length }})</h3>
                    <div v-if="!openItems.length" class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-400">
                        Nimic in lucru. Bubesti un inbox item cu „De facut“ sau completezi repede un task (vezi mai jos).
                    </div>
                    <div v-for="item in openItems" :key="item.id" class="mb-3 rounded-xl bg-white p-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <button
                                type="button"
                                class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border-2 border-slate-300 text-transparent transition hover:border-blue-400 hover:text-blue-500"
                                @click="toggleDone(item)"
                            >
                                ✓
                            </button>
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-slate-900">{{ item.title }}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ typeLabels[item.type] }}</span>
                                    <span v-if="item.project" class="text-slate-400">{{ item.project.name }}</span>
                                    <span v-if="item.due_at" class="text-slate-400">Termen: {{ formatDate(item.due_at) }}</span>
                                    <span v-if="item.priority !== 'normal'" class="rounded-full px-2 py-0.5 font-medium" :class="priorityClasses[item.priority]">{{ priorityLabels[item.priority] }}</span>
                                </div>
                            </div>
                            <button type="button" class="text-sm font-semibold text-red-400 hover:text-red-600" @click="remove(item)">Sterge</button>
                        </div>
                    </div>
                </section>

                <details class="mt-8 rounded-xl bg-white p-4 shadow-sm">
                    <summary class="cursor-pointer text-sm font-semibold uppercase tracking-wide text-slate-500">Gata ({{ doneItems.length }})</summary>
                    <div class="mt-3 space-y-2">
                        <div v-for="item in doneItems" :key="item.id" class="flex items-center gap-3 border-t border-slate-100 pt-2">
                            <button type="button" class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border-2 border-green-500 bg-green-500 text-white" @click="toggleDone(item)">✓</button>
                            <div class="min-w-0 flex-1">
                                <span class="text-slate-400 line-through">{{ item.title }}</span>
                            </div>
                            <button type="button" class="text-sm font-semibold text-red-400 hover:text-red-600" @click="remove(item)">Sterge</button>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </AuthenticatedLayout>
</template>