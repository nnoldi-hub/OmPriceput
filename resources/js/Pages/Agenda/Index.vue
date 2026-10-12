<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';

const props = defineProps({ items: Array, projects: Array, tags: Array, tag: String });
const page = usePage();

const typeLabels = { task: 'Task', nota: 'Nota', idee: 'Idee', obiectiv: 'Obiectiv', masuratoare: 'Masuratoare' };
const priorityClasses = { low: 'bg-slate-100 text-slate-600', normal: 'bg-blue-100 text-blue-700', high: 'bg-red-100 text-red-700' };
const priorityLabels = { low: 'Scazuta', normal: 'Normala', high: 'Ridicata' };
const tagPalette = ['bg-blue-100 text-blue-700', 'bg-amber-100 text-amber-700', 'bg-emerald-100 text-emerald-700', 'bg-purple-100 text-purple-700', 'bg-pink-100 text-pink-700', 'bg-cyan-100 text-cyan-700'];

const activeTag = computed(() => props.tag ?? '');
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
        repeat: item.repeat ?? null,
        ...overrides,
    }, { preserveScroll: true });
}

function patchTags(item, raw) {
    const tags = raw.split(',').map((name) => name.trim()).filter(Boolean);
    router.patch(route('agenda.items.update', item.id), { tags }, { preserveScroll: true, onError: () => refocus() });
}

function tagsText(item) {
    return (item.tags ?? []).map((tag) => tag.name).join(', ');
}

function tagClasses(name) {
    let hash = 0;
    for (const char of String(name)) hash = (hash * 31 + char.codePointAt(0)) % 997;
    return tagPalette[hash % tagPalette.length];
}

function filterTag(name) {
    router.get(route('agenda.inbox', name ? { tag: name } : {}), {}, { preserveScroll: true });
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
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Inbox ({{ inboxItems.length }})</h3>
                        <div v-if="tags.length" class="flex flex-wrap gap-1.5">
                            <button
                                type="button"
                                class="rounded-full px-2.5 py-1 text-xs font-medium transition"
                                :class="activeTag === '' ? 'bg-slate-800 text-white' : 'bg-white text-slate-500 ring-1 ring-slate-200 hover:bg-slate-50'"
                                @click="filterTag('')"
                            >
                                Toate
                            </button>
                            <button
                                v-for="tag in tags"
                                :key="tag.id"
                                type="button"
                                class="rounded-full px-2.5 py-1 text-xs font-medium transition"
                                :class="activeTag === tag.name ? 'bg-slate-800 text-white' : 'bg-white text-slate-500 ring-1 ring-slate-200 hover:bg-slate-50'"
                                @click="filterTag(tag.name)"
                            >
                                #{{ tag.name }} ({{ tag.agenda_items_count }})
                            </button>
                        </div>
                    </div>
                    <div v-if="!inboxItems.length" class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-400">
                        Inbox-ul e gol. Tasteaza deasupra orice idee, nota sau task.
                    </div>
                    <div v-for="item in inboxItems" :key="item.id" class="mb-3 rounded-xl bg-white p-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-slate-900">{{ item.title }}</div>
                                <div v-if="item.text" class="mt-1 whitespace-pre-line text-sm text-slate-500">{{ item.text }}</div>
                                <div v-if="item.tags?.length" class="mt-1.5 flex flex-wrap gap-1.5">
                                    <span v-for="tag in item.tags" :key="tag.id" class="rounded-full px-2 py-0.5 text-xs" :class="tagClasses(tag.name)">#{{ tag.name }}</span>
                                </div>
                            </div>
                            <button type="button" class="text-sm font-semibold text-blue-600 hover:text-blue-500" @click="moveToDo(item)">De facut</button>
                            <button type="button" class="text-sm font-semibold text-red-400 hover:text-red-600" @click="remove(item)">Sterge</button>
                        </div>
                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-4">
                            <select v-model="item.type" class="rounded-md border-slate-300 text-sm" @change="patch(item)">
                                <option v-for="(label, value) in typeLabels" :key="value" :value="value">{{ label }}</option>
                            </select>
                            <select v-model.number="item.project_id" class="rounded-md border-slate-300 text-sm" @change="patch(item)">
                                <option :value="null">Fara proiect</option>
                                <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
                            </select>
                            <input v-model="item.due_at" type="date" class="rounded-md border-slate-300 text-sm" @change="patch(item)" />
                            <select v-model="item.repeat" class="rounded-md border-slate-300 text-sm" @change="patch(item)">
                                <option :value="null">Fara repetare</option>
                                <option value="daily">Zilnic</option>
                                <option value="3d">La 3 zile</option>
                                <option value="weekly">Saptamanal</option>
                                <option value="monthly">Lunar</option>
                            </select>
                        </div>
                        <input
                            :value="tagsText(item)"
                            type="text"
                            placeholder="Etichete: separate cu virgula (ex: utilaj, iarna, plafon)"
                            class="mt-2 block w-full rounded-md border-slate-300 text-sm"
                            enterkeyhint="done"
                            @keyup.enter="patchTags(item, $event.target.value)"
                            @change="patchTags(item, $event.target.value)"
                        />
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
                                    <span v-if="item.project"><Link :href="route('agenda.projects.show', item.project.id)" class="text-blue-600 hover:text-blue-500">{{ item.project.name }}</Link></span>
                                    <span v-if="item.due_at" class="text-slate-400">Termen: {{ formatDate(item.due_at) }}</span>
                                    <span v-if="item.repeat" class="rounded-full bg-purple-100 px-2 py-0.5 text-purple-700">repetabil</span>
                                    <span v-if="item.priority !== 'normal'" class="rounded-full px-2 py-0.5 font-medium" :class="priorityClasses[item.priority]">{{ priorityLabels[item.priority] }}</span>
                                    <span v-for="tag in item.tags ?? []" :key="tag.id" class="rounded-full px-2 py-0.5" :class="tagClasses(tag.name)">#{{ tag.name }}</span>
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