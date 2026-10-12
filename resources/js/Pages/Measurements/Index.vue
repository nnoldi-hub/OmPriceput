<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';

const props = defineProps({ measurements: Array, clients: Array, statuses: Object, measurementsCount: Number });
const page = usePage();

const capture = ref('');
const captureClient = ref(null);
const captureInput = ref(null);

const totalArea = (m) => m.objects.reduce((sum, object) => sum + (object.area ?? 0), 0);
const totalPerimeter = (m) => m.objects.reduce((sum, object) => sum + (object.perimeter ?? 0), 0);

const openMeasurements = computed(() => props.measurements.filter((m) => m.status === 'in_progress'));
const completedMeasurements = computed(() => props.measurements.filter((m) => m.status === 'completed'));

function start() {
    const name = capture.value.trim();
    if (!name) return;
    router.post(route('measurements.store'), { name, client_id: captureClient.value }, {
        preserveScroll: true,
        onSuccess: () => { capture.value = ''; captureClient.value = null; refocus(); },
    });
}

function toggleDone(m) {
    const status = m.status === 'completed' ? 'in_progress' : 'completed';
    router.patch(route('measurements.update', m.id), { status }, { preserveScroll: true });
}

function patchObject(m, object, overrides = {}) {
    router.patch(route('measurements.objects.update', object.id), {
        name: object.name,
        length: object.length ?? null,
        width: object.width ?? null,
        height: object.height ?? null,
        quantity: object.quantity ?? 1,
        notes: object.notes ?? null,
        ...overrides,
    }, { preserveScroll: true });
}

function addObject(m) {
    router.post(route('measurements.objects.store', m.id), { name: m._newObjectName ?? '' }, {
        preserveScroll: true,
        onSuccess: () => { m._newObjectName = ''; },
    });
}

function remove(m) {
    if (confirm(`Stergi masuratoarea "${m.name}"?`)) router.delete(route('measurements.destroy', m.id), { preserveScroll: true });
}

function removeObject(object) {
    if (confirm(`Stergi "${object.name}"?`)) router.delete(route('measurements.objects.destroy', object.id), { preserveScroll: true });
}

function formatDate(value) {
    if (!value) return '';
    return new Date(value).toLocaleDateString('ro-RO', { day: 'numeric', month: 'short', year: 'numeric' });
}

function refocus() {
    nextTick(() => captureInput.value?.focus());
}

onMounted(refocus);
</script>

<template>
    <Head title="Masuratori" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Masuratori</h2>
                <span class="hidden text-sm text-slate-400 sm:block">Noteaza dimensiunile de pe santier</span>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div v-if="page.props.flash.success" class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ page.props.flash.success }}</div>

                <form @submit.prevent="start" class="rounded-xl bg-white p-4 shadow-sm">
                    <label class="block text-xs font-medium uppercase tracking-wide text-slate-500">Masuratoare noua</label>
                    <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                        <input
                            ref="captureInput"
                            v-model="capture"
                            type="text"
                            autocomplete="off"
                            placeholder="Ex: Casa Smecheru, apartament Morarilor..."
                            class="w-full rounded-xl border-slate-300 px-4 py-3 text-lg shadow-sm outline-none focus:border-brand-navy focus:ring-brand-navy"
                            enterkeyhint="done"
                        />
                        <div class="flex gap-2">
                            <select v-model="captureClient" class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm shadow-sm sm:w-48" @change="refocus()">
                                <option :value="null">Fara client</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                            </select>
                            <button type="submit" class="shrink-0 rounded-xl bg-brand-navy px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700">Incepe</button>
                        </div>
                    </div>
                </form>

                <section class="mt-8">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">In lucru ({{ openMeasurements.length }})</h3>
                    <div v-if="!openMeasurements.length" class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-400">
                        Nimic de masurat. Noteaza numele, apoi adauga camerele cu dimensiuni.
                    </div>
                    <div v-for="m in openMeasurements" :key="m.id" class="mb-3 rounded-xl bg-white p-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-slate-900">{{ m.name }}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                    <span v-if="m.location" class="text-slate-400">{{ m.location }}</span>
                                    <span v-if="m.client_name">{{ m.client_name }}</span>
                                    <span v-if="m.project_name" class="text-slate-400">{{ m.project_name }}</span>
                                    <span v-if="m.measured_at">Masurata: {{ formatDate(m.measured_at) }}</span>
                                    <span class="font-semibold text-brand-navy">{{ totalArea(m) }} mp</span>
                                    <span class="text-slate-400">{{ m.objects_count }} obiecte</span>
                                </div>
                            </div>
                            <button type="button" class="text-sm font-semibold text-green-600 hover:text-green-500" @click="toggleDone(m)">Final</button>
                            <button type="button" class="text-sm font-semibold text-red-400 hover:text-red-600" @click="remove(m)">Sterge</button>
                        </div>

                        <details class="mt-3" :open="m.objects_count === 0">
                            <summary class="cursor-pointer text-sm font-semibold text-slate-600">Obiecte ({{ m.objects_count }}) · {{ totalArea(m) }} mp · {{ totalPerimeter(m) }} ml</summary>
                            <div class="mt-3 space-y-2">
                                <div v-for="o in m.objects" :key="o.id" class="rounded-lg bg-slate-50 p-2">
                                    <div class="flex items-center gap-2">
                                        <input v-model="o.name" type="text" placeholder="Camera / zona" class="min-w-0 flex-1 rounded-md border-slate-300 text-sm" enterkeyhint="done" @change="patchObject(m, o)" />
                                        <input v-model="o.length" type="number" step="0.01" inputmode="decimal" placeholder="L" title="Lungime (m)" class="w-20 rounded-md border-slate-300 text-sm" enterkeyhint="done" @change="patchObject(m, o)" />
                                        <input v-model="o.width" type="number" step="0.01" inputmode="decimal" placeholder="l" title="Latime (m)" class="w-20 rounded-md border-slate-300 text-sm" enterkeyhint="done" @change="patchObject(m, o)" />
                                        <input v-model="o.quantity" type="number" min="1" inputmode="numeric" placeholder="buc" title="Cantitate" class="w-16 rounded-md border-slate-300 text-sm" enterkeyhint="done" @change="patchObject(m, o)" />
                                        <span class="w-16 text-right text-xs font-medium text-slate-600">{{ o.area }} mp</span>
                                        <button type="button" class="text-sm font-semibold text-red-400 hover:text-red-600" @click="removeObject(o)">x</button>
                                    </div>
                                </div>
                                <div class="flex gap-2 pt-1">
                                    <input v-model="m._newObjectName" type="text" placeholder="Camera noua (ex: Living)" class="min-w-0 flex-1 rounded-md border-slate-300 text-sm" enterkeyhint="send" @keyup.enter="addObject(m)" />
                                    <button type="button" class="shrink-0 rounded-md bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white" @click="addObject(m)">Adauga</button>
                                </div>
                            </div>
                        </details>
                    </div>
                </section>

                <details v-if="completedMeasurements.length" class="mt-8 rounded-xl bg-white p-4 shadow-sm">
                    <summary class="cursor-pointer text-sm font-semibold uppercase tracking-wide text-slate-500">Finalizate ({{ completedMeasurements.length }})</summary>
                    <div class="mt-3 space-y-2">
                        <div v-for="m in completedMeasurements" :key="m.id" class="flex items-center gap-3 border-t border-slate-100 pt-2">
                            <button type="button" class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border-2 border-green-500 bg-green-500 text-white" @click="toggleDone(m)">✓</button>
                            <div class="min-w-0 flex-1">
                                <div class="text-slate-600 line-through">{{ m.name }}</div>
                                <div class="text-xs text-slate-400">{{ m.client_name ?? '' }} · {{ totalArea(m) }} mp</div>
                            </div>
                            <button type="button" class="text-sm font-semibold text-red-400 hover:text-red-600" @click="remove(m)">Sterge</button>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </AuthenticatedLayout>
</template>