<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

const { canManage } = usePermissions();

const props = defineProps({
    view: String,
    date: String,
    rangeStart: String,
    rangeEnd: String,
    technicianId: [Number, String, null],
    technicians: Array,
    days: Array,
    installations: Array,
});

const filters = reactive({
    date: props.date,
    technician_id: props.technicianId ?? '',
    view: props.view,
});

watch(filters, () => router.get(route('technical.installations.calendar'), filters, { preserveState: true, replace: true }));

const statusLabels = { scheduled: 'Programata', in_progress: 'In desfasurare', completed: 'Finalizata' };
const statusClasses = { scheduled: 'bg-blue-100 text-blue-700', in_progress: 'bg-amber-100 text-amber-800', completed: 'bg-green-100 text-green-800' };

function time(value) {
    return value ? new Date(value).toLocaleTimeString('ro-RO', { hour: '2-digit', minute: '2-digit' }) : '-';
}

function dayLabel(dateStr, style = 'long') {
    return new Date(`${dateStr}T12:00:00`).toLocaleDateString('ro-RO', style === 'long'
        ? { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }
        : { weekday: 'short', day: 'numeric', month: 'short' });
}

function dayNumber(dateStr) {
    return new Date(`${dateStr}T12:00:00`).getDate();
}

function setView(view) {
    filters.view = view;
}

function navigate(direction) {
    const current = new Date(`${filters.date}T12:00:00`);

    if (filters.view === 'month') {
        current.setMonth(current.getMonth() + direction);
    } else if (filters.view === 'week') {
        current.setDate(current.getDate() + direction * 7);
    } else {
        current.setDate(current.getDate() + direction);
    }

    filters.date = current.toISOString().slice(0, 10);
}

function goToday() {
    filters.date = new Date().toISOString().slice(0, 10);
}

const rangeLabel = computed(() => {
    if (props.view === 'day') {
        return dayLabel(props.date);
    }

    if (props.view === 'week') {
        return `${dayLabel(props.rangeStart, 'short')} - ${dayLabel(props.rangeEnd, 'short')}`;
    }

    return new Date(`${props.date}T12:00:00`).toLocaleDateString('ro-RO', { month: 'long', year: 'numeric' });
});

const weekDayHeaders = ['Luni', 'Marti', 'Miercuri', 'Joi', 'Vineri', 'Sambata', 'Duminica'];
</script>

<template>
    <Head title="Calendar programari" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Calendar programari</h2>
                <Link v-if="canManage('installations')" :href="route('technical.installations.create')" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white">Programare noua</Link>
            </div>
        </template>
        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-3 rounded-lg bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50" @click="navigate(-1)">&laquo;</button>
                        <button type="button" class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50" @click="goToday">Azi</button>
                        <button type="button" class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50" @click="navigate(1)">&raquo;</button>
                        <span class="ml-2 text-sm font-semibold capitalize text-slate-900">{{ rangeLabel }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex rounded-md border border-slate-300 text-sm">
                            <button
                                type="button"
                                class="px-3 py-2"
                                :class="filters.view === 'day' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50'"
                                @click="setView('day')"
                            >Zi</button>
                            <button
                                type="button"
                                class="border-l border-slate-300 px-3 py-2"
                                :class="filters.view === 'week' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50'"
                                @click="setView('week')"
                            >Saptamana</button>
                            <button
                                type="button"
                                class="border-l border-slate-300 px-3 py-2"
                                :class="filters.view === 'month' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50'"
                                @click="setView('month')"
                            >Luna</button>
                        </div>
                        <select v-model="filters.technician_id" class="rounded-md border-slate-300 text-sm">
                            <option value="">Toti tehnicienii</option>
                            <option v-for="tech in technicians" :key="tech.id" :value="tech.id">{{ tech.name }}</option>
                        </select>
                        <Link :href="route('technical.installations.index')" class="rounded-md border border-slate-300 px-3 py-2 text-center text-sm text-slate-600">Lista programari</Link>
                    </div>
                </div>

                <!-- Day view -->
                <div v-if="filters.view === 'day'" class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold capitalize text-slate-900">{{ dayLabel(date) }}</h3>
                    <div v-if="installations.length" class="mt-5 divide-y divide-slate-100">
                        <Link v-for="installation in installations" :key="installation.id" :href="route('technical.installations.show', installation.id)" class="flex gap-4 py-4 hover:bg-slate-50">
                            <div class="w-16 flex-shrink-0 text-lg font-semibold text-blue-600">{{ time(installation.scheduled_at) }}</div>
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-slate-900">{{ installation.client.name }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ installation.type }} · {{ installation.technician?.name ?? 'Neasignat' }}</div>
                                <div v-if="installation.address" class="mt-1 text-xs text-slate-400">{{ installation.address }}</div>
                            </div>
                            <span class="h-fit rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses[installation.status]">{{ statusLabels[installation.status] }}</span>
                        </Link>
                    </div>
                    <p v-else class="mt-6 text-sm text-slate-400">Nu exista programari pentru criteriile selectate.</p>
                </div>

                <!-- Week view -->
                <div v-else-if="filters.view === 'week'" class="grid grid-cols-1 gap-3 sm:grid-cols-7">
                    <div v-for="day in days" :key="day.date" class="rounded-lg bg-white p-3 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="text-xs font-semibold uppercase text-slate-500">{{ dayLabel(day.date, 'short') }}</div>
                            <span v-if="day.isToday" class="rounded-full bg-blue-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">azi</span>
                        </div>
                        <div class="mt-2 space-y-2">
                            <Link
                                v-for="installation in day.installations"
                                :key="installation.id"
                                :href="route('technical.installations.show', installation.id)"
                                class="block rounded-md p-2 text-xs hover:bg-slate-50"
                                :class="statusClasses[installation.status]"
                            >
                                <div class="font-semibold">{{ time(installation.scheduled_at) }}</div>
                                <div class="truncate">{{ installation.client.name }}</div>
                                <div class="truncate text-[11px] opacity-75">{{ installation.technician?.name ?? 'Neasignat' }}</div>
                            </Link>
                            <p v-if="!day.installations.length" class="text-[11px] text-slate-300">-</p>
                        </div>
                    </div>
                </div>

                <!-- Month view -->
                <div v-else class="rounded-lg bg-white p-4 shadow-sm">
                    <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold uppercase text-slate-400">
                        <div v-for="header in weekDayHeaders" :key="header">{{ header }}</div>
                    </div>
                    <div class="mt-2 grid grid-cols-7 gap-1">
                        <div
                            v-for="day in days"
                            :key="day.date"
                            class="min-h-[100px] rounded-md border border-slate-100 p-1.5"
                            :class="[day.inMonth ? 'bg-white' : 'bg-slate-50', day.isToday ? 'ring-2 ring-blue-500' : '']"
                        >
                            <div class="text-xs font-semibold" :class="day.inMonth ? 'text-slate-700' : 'text-slate-300'">{{ dayNumber(day.date) }}</div>
                            <div class="mt-1 space-y-1">
                                <Link
                                    v-for="installation in day.installations.slice(0, 3)"
                                    :key="installation.id"
                                    :href="route('technical.installations.show', installation.id)"
                                    class="block truncate rounded px-1 py-0.5 text-[10px]"
                                    :class="statusClasses[installation.status]"
                                >
                                    {{ time(installation.scheduled_at) }} {{ installation.client.name }}
                                </Link>
                                <div v-if="day.installations.length > 3" class="text-[10px] text-slate-400">+{{ day.installations.length - 3 }} mai multe</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
