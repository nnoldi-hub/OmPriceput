<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ measurement: Object, clients: Array, projects: Array, statuses: Object, objectTypes: Object, fieldDefs: Object, pricing: Object });
const page = usePage();

const m = props.measurement;
const location = ref(m.location ?? '');
const notes = ref(m.notes ?? '');
const clientId = ref(m.client_id ?? null);
const projectId = ref(m.project_id ?? null);
const photoInput = ref(null);

function blankObject(type) {
    return { type, name: '', length: null, width: null, height: null, quantity: 1, specs: {} };
}

const newObject = ref(blankObject('camera'));

const typeKeys = Object.keys(props.objectTypes ?? {});
const baseFields = (type) => props.objectTypes?.[type]?.fields ?? [];
const attrFields = (type) => props.objectTypes?.[type]?.attributes ?? [];
const typeLabel = (type) => props.objectTypes?.[type]?.label ?? type;

function onNewTypeChange() {
    newObject.value = blankObject(newObject.value.type);
}

const totalArea = computed(() => m.objects.reduce((sum, object) => sum + (object.area ?? 0), 0));
const totalPerimeter = computed(() => m.objects.reduce((sum, object) => sum + (object.perimeter ?? 0), 0));
const totalPieces = computed(() => m.objects.filter((o) => o.unit === 'buc').reduce((sum, o) => sum + (o.quantity ?? 0), 0));
const estimate = computed(() => {
    let sum = totalArea.value * props.pricing.area_m2 + totalPerimeter.value * props.pricing.perimeter_ml;
    for (const o of m.objects) {
        if (o.object_type === 'camera') continue;
        const price = props.pricing.type_prices?.[o.object_type] ?? (o.unit === 'ml' ? props.pricing.perimeter_ml : 0);
        sum += (o.unit_quantity ?? 0) * price;
    }
    return sum;
});

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
        object_type: object.object_type,
        length: object.length ?? null,
        width: object.width ?? null,
        height: object.height ?? null,
        quantity: object.quantity ?? 1,
        specs: object.specs ?? {},
        notes: object.notes ?? null,
        ...overrides,
    }, { preserveScroll: true });
}

function addObject() {
    const o = newObject.value;
    const name = o.name.trim();
    if (!name) return;
    router.post(route('measurements.objects.store', m.id), {
        name,
        object_type: o.type,
        length: o.length ?? null,
        width: o.width ?? null,
        height: o.height ?? null,
        quantity: o.quantity || 1,
        specs: o.specs ?? {},
    }, {
        preserveScroll: true,
        onSuccess: () => { newObject.value = blankObject(o.type); },
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

                <section class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl bg-white p-4 text-center shadow-sm">
                        <div class="text-2xl font-bold text-brand-navy">{{ Number(totalArea).toFixed(1) }}</div>
                        <div class="text-xs text-slate-500">mp</div>
                    </div>
                    <div class="rounded-xl bg-white p-4 text-center shadow-sm">
                        <div class="text-2xl font-bold text-brand-navy">{{ Number(totalPerimeter).toFixed(1) }}</div>
                        <div class="text-xs text-slate-500">ml</div>
                    </div>
                    <div class="rounded-xl bg-white p-4 text-center shadow-sm">
                        <div class="text-2xl font-bold text-brand-navy">{{ totalPieces }}</div>
                        <div class="text-xs text-slate-500">buc</div>
                    </div>
                    <div class="rounded-xl bg-brand-navy p-4 text-center text-white shadow-sm">
                        <div class="text-2xl font-bold">{{ ron(estimate) }}</div>
                        <div class="text-xs text-slate-300">estimativ oferta</div>
                    </div>
                </section>

                <section class="mt-5 rounded-xl bg-white p-4 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Elemente masurate ({{ m.objects.length }})</h3>
                    <div class="mt-3 space-y-3">
                        <div v-for="o in m.objects" :key="o.id" class="rounded-lg bg-slate-50 p-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-brand-navy px-2 py-0.5 text-xs font-semibold text-white">{{ o.type_label }}</span>
                                <input v-model="o.name" type="text" placeholder="Denumire / zona" class="min-w-0 flex-1 rounded-md border-slate-300 text-sm" enterkeyhint="done" @change="patchObject(o)" />
                                <span class="text-xs font-semibold text-slate-600">{{ Number(o.unit_quantity ?? 0).toFixed(1) }} {{ o.unit }}</span>
                                <button type="button" class="text-sm font-semibold text-red-400 hover:text-red-600" @click="removeObject(o)">x</button>
                            </div>
                            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                <div v-for="f in baseFields(o.object_type)" :key="f">
                                    <label class="block text-[11px] uppercase tracking-wide text-slate-400">{{ fieldDefs[f]?.label }}<span v-if="fieldDefs[f]?.unit"> ({{ fieldDefs[f].unit }})</span></label>
                                    <input v-model.number="o[f]" type="number" step="0.01" inputmode="decimal" class="mt-0.5 w-full rounded-md border-slate-300 text-sm" enterkeyhint="done" @change="patchObject(o)" />
                                </div>
                                <div v-for="f in attrFields(o.object_type)" :key="f">
                                    <label class="block text-[11px] uppercase tracking-wide text-slate-400">{{ fieldDefs[f]?.label }}</label>
                                    <select v-if="fieldDefs[f]?.type === 'select'" v-model="o.specs[f]" class="mt-0.5 w-full rounded-md border-slate-300 text-sm" @change="patchObject(o)">
                                        <option value="">-</option>
                                        <option v-for="opt in fieldDefs[f].options" :key="opt" :value="opt">{{ opt }}</option>
                                    </select>
                                    <input v-else v-model="o.specs[f]" :type="fieldDefs[f]?.type === 'number' ? 'number' : 'text'" class="mt-0.5 w-full rounded-md border-slate-300 text-sm" enterkeyhint="done" @change="patchObject(o)" />
                                </div>
                                <div>
                                    <label class="block text-[11px] uppercase tracking-wide text-slate-400">Cantitate</label>
                                    <input v-model.number="o.quantity" type="number" min="1" inputmode="numeric" class="mt-0.5 w-full rounded-md border-slate-300 text-sm" enterkeyhint="done" @change="patchObject(o)" />
                                </div>
                            </div>
                            <p v-if="o.attributes_summary" class="mt-1 text-xs text-slate-500">{{ o.attributes_summary }}</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-lg border border-dashed border-slate-300 p-3">
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <div class="col-span-2">
                                <label class="block text-[11px] uppercase tracking-wide text-slate-400">Tip element</label>
                                <select v-model="newObject.type" class="mt-0.5 w-full rounded-md border-slate-300 text-sm" @change="onNewTypeChange">
                                    <option v-for="key in typeKeys" :key="key" :value="key">{{ typeLabel(key) }}</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[11px] uppercase tracking-wide text-slate-400">Denumire / zona</label>
                                <input v-model="newObject.name" type="text" placeholder="Ex: Living, Priza bucatarie" class="mt-0.5 w-full rounded-md border-slate-300 text-sm" enterkeyhint="done" @keyup.enter="addObject" />
                            </div>
                            <div v-for="f in baseFields(newObject.type)" :key="f">
                                <label class="block text-[11px] uppercase tracking-wide text-slate-400">{{ fieldDefs[f]?.label }}<span v-if="fieldDefs[f]?.unit"> ({{ fieldDefs[f].unit }})</span></label>
                                <input v-model.number="newObject[f]" type="number" step="0.01" inputmode="decimal" class="mt-0.5 w-full rounded-md border-slate-300 text-sm" enterkeyhint="done" />
                            </div>
                            <div v-for="f in attrFields(newObject.type)" :key="f">
                                <label class="block text-[11px] uppercase tracking-wide text-slate-400">{{ fieldDefs[f]?.label }}</label>
                                <select v-if="fieldDefs[f]?.type === 'select'" v-model="newObject.specs[f]" class="mt-0.5 w-full rounded-md border-slate-300 text-sm">
                                    <option value="">-</option>
                                    <option v-for="opt in fieldDefs[f].options" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                                <input v-else v-model="newObject.specs[f]" :type="fieldDefs[f]?.type === 'number' ? 'number' : 'text'" class="mt-0.5 w-full rounded-md border-slate-300 text-sm" enterkeyhint="done" />
                            </div>
                            <div>
                                <label class="block text-[11px] uppercase tracking-wide text-slate-400">Cantitate</label>
                                <input v-model.number="newObject.quantity" type="number" min="1" inputmode="numeric" class="mt-0.5 w-full rounded-md border-slate-300 text-sm" enterkeyhint="done" />
                            </div>
                        </div>
                        <button type="button" class="mt-3 w-full rounded-md bg-slate-800 px-3 py-2 text-sm font-semibold text-white sm:w-auto" @click="addObject">Adauga element</button>
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
                    Tarife: suprafata {{ ron(pricing.area_m2) }}/mp, traseu {{ ron(pricing.perimeter_ml) }}/ml, restul pe tip in
                    <Link :href="route('admin.pricing.index')" class="font-semibold text-blue-500 hover:text-blue-600">nomenclator</Link>.
                    Masurata: {{ formatDate(m.measured_at) }}.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>