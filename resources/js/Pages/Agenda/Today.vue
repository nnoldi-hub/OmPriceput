<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';

const props = defineProps({ overdue: Array, dueToday: Array, appointments: Array, reminders: Array });
const page = usePage();

const typeLabels = { task: 'Task', nota: 'Nota', idee: 'Idee', obiectiv: 'Obiectiv', masuratoare: 'Masuratoare' };
const installationLabels = { instalare: 'Instalare / montaj', reparatie: 'Reparatie', mentenanta: 'Mentenanta', verificare: 'Verificare / constatare', urgenta: 'Interventa urgenta' };

const capture = ref('');
const captureInput = ref(null);

const todayLabel = computed(() =>
    new Date().toLocaleDateString('ro-RO', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
);

const totalOpen = computed(() => props.overdue.length + props.dueToday.length + props.reminders.length);

function add() {
    const title = capture.value.trim();
    if (!title) return;
    router.post(route('agenda.items.store'), { title }, {
        preserveScroll: true,
        onSuccess: () => { capture.value = ''; refocus(); },
    });
}

function toggle(item) {
    router.patch(route('agenda.items.toggle', item.id), {}, { preserveScroll: true });
}

function timeLabel(value) {
    if (!value) return '';
    return new Date(value).toLocaleTimeString('ro-RO', { hour: '2-digit', minute: '2-digit' });
}

function dueLabel(value) {
    if (!value) return '';
    return `${new Date(value).toLocaleDateString('ro-RO', { day: 'numeric', month: 'short' })}, ${timeLabel(value)}`;
}

function refocus() {
    nextTick(() => captureInput.value?.focus());
}

onMounted(refocus);
</script>

<template>
    <Head title="Azi" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Azi</h2>
                    <p class="text-sm text-slate-500 first-letter:uppercase">{{ todayLabel }}</p>
                </div>
                <Link :href="route('agenda.inbox')" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-600 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">
                    Inbox
                </Link>
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

                <div class="mt-4 rounded-lg bg-white p-4 text-sm text-slate-500 shadow-sm">
                    <span class="font-semibold text-slate-700">{{ totalOpen }}</span> lucruri de facut azi.
                </div>

                <section class="mt-6">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-red-500">Intarziate ({{ overdue.length }})</h3>
                    <div v-if="!overdue.length" class="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-center text-sm text-slate-400">Nimic intarziat.</div>
                    <div v-for="item in overdue" :key="item.id" class="mb-2 rounded-xl bg-white p-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <button type="button" class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border-2 border-red-200 text-transparent transition hover:border-red-400 hover:text-red-400" @click="toggle(item)">✓</button>
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-slate-900">{{ item.title }}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                    <span class="font-semibold text-red-500">Termen: {{ dueLabel(item.due_at) }}</span>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ typeLabels[item.type] }}</span>
                                    <span v-if="item.project" class="text-slate-400">{{ item.project.name }}</span>
                                    <span v-if="item.repeat" class="rounded-full bg-purple-100 px-2 py-0.5 text-purple-700">repetabil</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mt-6">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-orange-600">Cu termen azi ({{ dueToday.length }})</h3>
                    <div v-if="!dueToday.length" class="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-center text-sm text-slate-400">Nicio sarcina cu termen azi.</div>
                    <div v-for="item in dueToday" :key="item.id" class="mb-2 rounded-xl bg-white p-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <button type="button" class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border-2 border-slate-300 text-transparent transition hover:border-blue-400 hover:text-blue-500" @click="toggle(item)">✓</button>
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-slate-900">{{ item.title }}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                    <span class="font-semibold text-orange-600">{{ timeLabel(item.due_at) }}</span>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ typeLabels[item.type] }}</span>
                                    <span v-if="item.project" class="text-slate-400">{{ item.project.name }}</span>
                                    <span v-if="item.repeat" class="rounded-full bg-purple-100 px-2 py-0.5 text-purple-700">repetabil</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mt-6">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-blue-600">Programari azi ({{ appointments.length }})</h3>
                    <div v-if="!appointments.length" class="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-center text-sm text-slate-400">Nicio programare azi.</div>
                    <Link
                        v-for="appointment in appointments"
                        :key="appointment.id"
                        :href="route('technical.installations.show', appointment.id)"
                        class="mb-2 block rounded-xl bg-white p-4 shadow-sm transition hover:bg-slate-50"
                    >
                        <div class="flex items-start gap-3">
                            <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">{{ timeLabel(appointment.scheduled_at) }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-slate-900">{{ appointment.client?.name ?? 'Client' }}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                    <span class="rounded-full bg-blue-50 px-2 py-0.5 text-blue-700">{{ installationLabels[appointment.type] ?? appointment.type }}</span>
                                    <span v-if="appointment.address" class="truncate text-slate-400">{{ appointment.address }}</span>
                                </div>
                            </div>
                        </div>
                    </Link>
                </section>

                <section class="mt-6">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-amber-600">Mementouri ({{ reminders.length }})</h3>
                    <div v-if="!reminders.length" class="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-center text-sm text-slate-400">Niciun mementou.</div>
                    <div v-for="item in reminders" :key="item.id" class="mb-2 rounded-xl bg-white p-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <button type="button" class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border-2 border-amber-200 text-transparent transition hover:border-amber-500 hover:text-amber-500" @click="toggle(item)">✓</button>
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-slate-900">{{ item.title }}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-amber-700">mementou: {{ timeLabel(item.reminder_at) }}</span>
                                    <span v-if="item.project" class="text-slate-400">{{ item.project.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>