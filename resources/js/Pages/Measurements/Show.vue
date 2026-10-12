<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ measurement: Object, clients: Array, projects: Array, statuses: Object, pricing: Object });
const page = usePage();

const m = props.measurement;
const location = ref(m.location ?? '');
const notes = ref(m.notes ?? '');
const clientId = ref(m.client_id ?? null);
const projectId = ref(m.project_id ?? null);
const newObjectName = ref('');
const photoInput = ref(null);

const totalArea = computed(() => m.objects.reduce((sum, object) => sum + (object.area ?? 0), 0));
const totalPerimeter = computed(() => m.objects.reduce((sum, object) => sum + (object.perimeter ?? 0), 0));
const estimate = computed(() => totalArea.value * props.pricing.area_m2 + totalPerimeter.value * props.pricing.perimeter_ml);

const ron = (value) => new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(value ?? 0);

function patch(overrides = {}) {
    router.patch(route('measurements.update', m.id), {
        name: m.name,
        location: location.value ?? null,
        client_id: clientId.value ?? null,
        project_id: projectId.value ?? null,
        status: m.status,
        notes: notes.value ?? null,
        ...overrides,
    }, { preserveScroll: true });
}

function saveDetails() {
    patch();
}

function convertToOffer() {
    router.post(route('measurements.offer', m.id), { client_id: clientId.value }, { preserveScroll: true });
}

function toggleDone() {
    patch({ status: m.status === 'completed' ? 'in_progress' : 'completed' });
}

function remove() {
    if (confirm(`Stergi masuratoarea "${m.name}"?`)) router.delete(route('measurements.destroy', m.id));
}

function patchObject(object, overrides = {}) {
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

function addObject() {
    const name = newObjectName.value.trim();
    if (!name) return;
    router.post(route('measurements.objects.store', m.id), { name }, {
        preserveScroll: true,
        onSuccess: () => { newObjectName.value = ''; },
    });
}

function removeObject(object) {
    if (confirm(`Stergi "${object.name}"?`)) router.delete(route('measurements.objects.destroy', object.id), { preserveScroll: true });
}

function uploadPhotos(event) {
    const files = Array.from(event.target.files ?? []);
    if (!files.length) return;
    router.post(route('measurements.photos.store', m.id), { photos: files }, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => { if (photoInput.value) photoInput.value.value = ''; },
    });
}

function removePhoto(photo) {
    if (confirm('Stergi poza?')) {
        router.delete(route('measurements.photos.destroy', m.id), { photo }, { preserveScroll: true });
    }
}

function formatDate(value) {
    if (!value) return '';
    return new Date(value).toLocaleDateString('ro-RO', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<template>
    <Head title="Masuratoare" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <Link :href="route('measurements.index')" class="text-sm text-slate-500 hover:text-slate-700">&larr; Masuratori</Link>
                <div class="mt-1 flex items-center justify-between gap-3">
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ m.name }}</h2>
                    <div class="flex shrink-0 items-center gap-3">
                        <button type="button" class="text-sm font-semibold text-green-600 hover:text-green-500" @click="toggleDone">
                            {{ m.status === 'completed' ? 'Reactiv' : 'Final' }}
                        </button>
                        <button type="button" class="text-sm font-semibold text-red-400 hover:text-red-600" @click="remove">Sterge</button>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div v-if="page.props.flash.success" class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ page.props.flash.success }}</div>
                <div v-if="page.props.flash.error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">{{ page.props.flash.error }}</div>

                <section class="mb-5">
                    <Link
                        v-if="m.offer"
                        :href="route('sales.offers.show', m.offer.id)"
                        class="block w-full rounded-xl bg-green-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-green-500"
                    >
                        Oferta #{{ m.offer.id }}{{ m.offer.title ? ' - '.concat(m.offer.title) : '' }} ({{ ron(m.offer.total_amount) }}) - deschide
                    </Link>
                    <button
                        v-else
                        type="button"
                        class="w-full rounded-xl bg-brand-navy px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700"
                        @click="convertToOffer"
                    >
                        Converteste masuratoarea in oferta
                    </button>
                </section>

                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium uppercase tracking-wide text-slate-500">Locatie / adresa</label>
                            <input v-model="location" type="text" placeholder="Adresa santier" class="mt-1 w-full rounded-md border-slate-300 text-sm" enterkeyhint="done" @keyup.enter="saveDetails" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium uppercase tracking-wide text-slate-500">Client</label>
                            <select v-model="clientId" class="mt-1 w-full rounded-md border-slate-300 text-sm" @change="saveDetails">
                                <option :value="null">Fara client</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium uppercase tracking-wide text-slate-500">Proiect</label>
                            <select v-model="projectId" class="mt-1 w-full rounded-md border-slate-300 text-sm" @change="saveDetails">
                                <option :value="null">Fara proiect</option>
                                <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" class="mt-3 rounded-lg bg-brand-navy px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700" @click="saveDetails">Salveaza detaliile</button>
                </div>

                <section class="mt-5 rounded-xl bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Poze ({{ (m.photos ?? []).length }})</h3>
                        <label class="cursor-pointer rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">
                            Adauga poze
                            <input ref="photoInput" type="file" accept="image/*" multiple class="hidden" @change="uploadPhotos" />
                        </label>
                    </div>
                    <div v-if="!(m.photos ?? []).length" class="mt-3 rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-400">
                        Nici o poza de la santier. Faci poze si le atasezi aici.
                    </div>
                    <div v-else class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4">
                        <div v-for="photo in m.photos" :key="photo" class="group relative aspect-square overflow-hidden rounded-lg bg-slate-100">
                            <img :src="photo" alt="Poza santier" class="h-full w-full object-cover" />
                            <button
                                type="button"
                                class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-black/60 text-xs font-bold text-white opacity-0 transition group-hover:opacity-100"
                                @click="removePhoto(photo)"
                            >
                                x
                            </button>
                        </div>
                    </div>
                </section>

                <section class="mt-5 grid grid-cols-3 gap-3">
                    <div class="rounded-xl bg-white p-4 text-center shadow-sm">
                        <div class="text-2xl font-bold text-brand-navy">{{ Number(totalArea).toFixed(1) }}</div>
                        <div class="text-xs text-slate-500">mp</div>
                    </div>
                    <div class="rounded-xl bg-white p-4 text-center shadow-sm">
                        <div class="text-2xl font-bold text-brand-navy">{{ Number(totalPerimeter).toFixed(1) }}</div>
                        <div class="text-xs text-slate-500">ml</div>
                    </div>
                    <div class="rounded-xl bg-brand-navy p-4 text-center text-white shadow-sm">
                        <div class="text-2xl font-bold">{{ ron(estimate) }}</div>
                        <div class="text-xs text-slate-300">estimativ oferta</div>
                    </div>
                </section>

                <section class="mt-5 rounded-xl bg-white p-4 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Obiecte / camere ({{ m.objects.length }})</h3>
                    <div class="mt-3 space-y-2">
                        <div v-for="o in m.objects" :key="o.id" class="rounded-lg bg-slate-50 p-2">
                            <div class="flex items-center gap-2">
                                <input v-model="o.name" type="text" placeholder="Camera / zona" class="min-w-0 flex-1 rounded-md border-slate-300 text-sm" enterkeyhint="done" @change="patchObject(o)" />
                                <input v-model="o.length" type="number" step="0.01" inputmode="decimal" placeholder="L" title="Lungime (m)" class="w-20 rounded-md border-slate-300 text-sm" enterkeyhint="done" @change="patchObject(o)" />
                                <input v-model="o.width" type="number" step="0.01" inputmode="decimal" placeholder="l" title="Latime (m)" class="w-20 rounded-md border-slate-300 text-sm" enterkeyhint="done" @change="patchObject(o)" />
                                <input v-model="o.quantity" type="number" min="1" inputmode="numeric" placeholder="buc" title="Cantitate" class="w-16 rounded-md border-slate-300 text-sm" enterkeyhint="done" @change="patchObject(o)" />
                                <span class="w-16 text-right text-xs font-medium text-slate-600">{{ Number(o.area ?? 0).toFixed(1) }} mp</span>
                                <button type="button" class="text-sm font-semibold text-red-400 hover:text-red-600" @click="removeObject(o)">x</button>
                            </div>
                        </div>
                        <div class="flex gap-2 pt-1">
                            <input v-model="newObjectName" type="text" placeholder="Camera noua (ex: Living)" class="min-w-0 flex-1 rounded-md border-slate-300 text-sm" enterkeyhint="send" @keyup.enter="addObject" />
                            <button type="button" class="shrink-0 rounded-md bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white" @click="addObject">Adauga</button>
                        </div>
                    </div>
                </section>

                <section class="mt-5 rounded-xl bg-white p-4 shadow-sm">
                    <label class="block text-sm font-semibold uppercase tracking-wide text-slate-500">Note</label>
                    <textarea v-model="notes" rows="4" placeholder="Observatii de la santier, acces, masuri de siguranta..." class="mt-2 w-full rounded-md border-slate-300 text-sm" />
                    <div class="mt-2 text-right">
                        <button type="button" class="rounded-lg bg-brand-navy px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700" @click="saveDetails">Salveaza notele</button>
                    </div>
                </section>

                <p class="mt-5 text-xs text-slate-400">
                    Tarife estimate: {{ ron(pricing.area_m2) }}/mp + {{ ron(pricing.perimeter_ml) }}/ml (modificabile in Setari). Masurata: {{ formatDate(m.measured_at) }}.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>