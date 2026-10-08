<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    page: Object,
    types: Object,
    trades: Object,
    services: Array,
    travel: Object,
});

const page = usePage();

const form = useForm({
    job_type: 'instalare',
    service_ids: [],
    scheduled_at: '',
    name: '',
    phone: '',
    email: '',
    city: '',
    address: '',
    notes: '',
    photos: [],
    privacy_consent: false,
});

const maxPhotos = 5;
const previews = ref([]);

const servicesByTrade = computed(() => {
    const grouped = {};
    props.services.forEach((service) => {
        (grouped[service.category] ||= []).push(service);
    });
    return grouped;
});

function toggleService(id) {
    const index = form.service_ids.indexOf(id);
    if (index === -1) {
        form.service_ids.push(id);
    } else {
        form.service_ids.splice(index, 1);
    }
}

// ---- Categorii pliabile ----
const openTrades = ref([]);

function toggleTrade(trade) {
    const i = openTrades.value.indexOf(trade);
    if (i === -1) {
        openTrades.value.push(trade);
    } else {
        openTrades.value.splice(i, 1);
    }
}

function selectedCount(list) {
    return list.filter((s) => form.service_ids.includes(s.id)).length;
}

// ---- Estimare buget ----
const selectedServices = computed(() => props.services.filter((s) => form.service_ids.includes(s.id)));
const totalPrice = computed(() => selectedServices.value.reduce((t, s) => t + Number(s.sale_price || 0), 0));
const totalMinutes = computed(() => selectedServices.value.reduce((t, s) => t + Number(s.duration_minutes || 0), 0));
const durationLabel = computed(() => {
    const m = totalMinutes.value;
    if (!m) return '';
    const h = Math.floor(m / 60);
    const r = m % 60;
    return h ? `${h} h${r ? ` ${r} min` : ''}` : `${r} min`;
});

const travelFee = computed(() => {
    const fee = Number(props.travel?.fee || 0);
    const freeAbove = Number(props.travel?.free_above || 0);
    return freeAbove > 0 && totalPrice.value >= freeAbove ? 0 : fee;
});
const grandTotal = computed(() => totalPrice.value + travelFee.value);

// ---- Calendar ----
const availableDates = ref([]);
const slots = ref([]);
const selectedDate = ref('');
const loadingDates = ref(false);
const loadingSlots = ref(false);

function serviceQuery(extra = {}) {
    const p = new URLSearchParams(extra);
    form.service_ids.forEach((id) => p.append('service_ids[]', id));
    return p.toString();
}

async function getJson(url) {
    const res = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error('request failed');
    return res.json();
}

async function loadDates() {
    loadingDates.value = true;
    selectedDate.value = '';
    slots.value = [];
    form.scheduled_at = '';
    try {
        const data = await getJson(`${route('public.quote.dates')}?${serviceQuery()}`);
        availableDates.value = data.dates;
    } catch {
        availableDates.value = [];
    } finally {
        loadingDates.value = false;
    }
}

async function pickDate(date) {
    selectedDate.value = date;
    form.scheduled_at = '';
    loadingSlots.value = true;
    try {
        const data = await getJson(`${route('public.quote.slots')}?${serviceQuery({ date })}`);
        slots.value = data.slots;
    } catch {
        slots.value = [];
    } finally {
        loadingSlots.value = false;
    }
}

function pickSlot(time) {
    form.scheduled_at = `${selectedDate.value} ${time}`;
}

function formatDate(iso) {
    return new Date(`${iso}T00:00`).toLocaleDateString('ro-RO', { weekday: 'short', day: 'numeric', month: 'short' });
}

// la orice schimbare a serviciilor se schimbă durata, deci recalculăm zilele
watch(() => form.service_ids.join(','), loadDates, { immediate: true });

// ---- Poze ----
function clearPhotos() {
    previews.value.forEach((url) => URL.revokeObjectURL(url));
    previews.value = [];
}

function handleFiles(event) {
    clearPhotos();
    form.photos = Array.from(event.target.files || []).slice(0, maxPhotos);
    previews.value = form.photos.map((file) => URL.createObjectURL(file));
}

function removePhoto(index) {
    URL.revokeObjectURL(previews.value[index]);
    previews.value.splice(index, 1);
    form.photos.splice(index, 1);
}

function submit() {
    form.post(route('public.lead.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            clearPhotos();
            loadDates();
        },
        onError: () => loadDates(),
    });
}
</script>

<template>
    <SeoHead
        title="Cere deviz - Om Priceput"
        description="Trimiteți cererea de deviz: alegeți lucrările, ora și atașați câteva fotografii. Primiți devizul scris după constatarea la fața locului."
    />

    <PublicLayout>
        <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="font-display text-3xl font-bold text-slate-900">Cerere de deviz</h1>
                <p class="mx-auto mt-3 max-w-2xl text-slate-500">
                    Precizați ce aveți nevoie și atașați câteva fotografii. Venim la constatare și primiți devizul scris,
                    cu materialele și manopera exacte. Taxa de deplasare se scade din deviz dacă ne încredințați lucrarea.
                </p>
            </div>

            <ol class="mt-8 grid grid-cols-1 gap-4 text-sm sm:grid-cols-4">
                <li class="rounded-lg border border-slate-200 bg-white p-4">
                    <span class="font-semibold text-orange-500">1.</span>
                    Trimiteți cererea
                    <p class="mt-1 text-slate-500">Tipul lucrării, fotografii și adresa.</p>
                </li>
                <li class="rounded-lg border border-slate-200 bg-white p-4">
                    <span class="font-semibold text-orange-500">2.</span>
                    Confirmăm ora
                    <p class="mt-1 text-slate-500">Vă anunțăm prin SMS sau telefon.</p>
                </li>
                <li class="rounded-lg border border-slate-200 bg-white p-4">
                    <span class="font-semibold text-orange-500">3.</span>
                    Constatare la locul lucrării
                    <p class="mt-1 text-slate-500">Analizăm problema și stabilim materialele.</p>
                </li>
                <li class="rounded-lg border border-slate-200 bg-white p-4">
                    <span class="font-semibold text-orange-500">4.</span>
                    Deviz scris
                    <p class="mt-1 text-slate-500">Îl primiți și decideți fără grabă.</p>
                </li>
            </ol>

            <div
                v-if="page.props.flash.success"
                class="mt-8 rounded-md bg-green-50 p-4 text-sm font-medium text-green-800"
            >
                {{ page.props.flash.success }}
            </div>

            <form class="mt-10 space-y-8" @submit.prevent="submit">
                <fieldset class="rounded-lg border border-slate-200 bg-white p-6">
                    <legend class="px-2 text-sm font-semibold text-slate-900">Ce aveți nevoie?</legend>

                    <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label
                            v-for="(label, value) in types"
                            :key="value"
                            class="flex cursor-pointer items-start gap-2 rounded-md border p-3 text-sm"
                            :class="form.job_type === value ? 'border-orange-500 bg-orange-50' : 'border-slate-200'"
                        >
                            <input
                                v-model="form.job_type"
                                type="radio"
                                :value="value"
                                class="mt-1 border-slate-300 text-orange-500 focus:ring-orange-500"
                            />
                            <span>
                                {{ label }}
                                <span v-if="value === 'verificare'" class="block text-xs text-slate-500">
                                    Analizăm problema fără să intervenim asupra instalației.
                                </span>
                                <span v-else-if="value === 'urgenta'" class="block text-xs text-slate-500">
                                    Executăm lucrarea în ziua cererii, în funcție de programul disponibil.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div v-if="services.length" class="mt-6">
                        <p class="text-sm font-medium text-slate-700">Ce lucrări vă interesează?</p>
                        <p class="text-xs text-slate-500">Opțional. Informația ne ajută să venim cu sculele și consumabilele potrivite.</p>

                        <div v-for="(list, trade) in servicesByTrade" :key="trade" class="mt-3 rounded-md border border-slate-200">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between px-4 py-3 text-left"
                                :aria-expanded="openTrades.includes(trade)"
                                @click="toggleTrade(trade)"
                            >
                                <span class="text-sm font-semibold text-slate-800">
                                    {{ trades[trade] ?? trade }}
                                    <span class="text-xs font-normal text-slate-500">({{ list.length }})</span>
                                </span>
                                <span class="flex items-center gap-2">
                                    <span
                                        v-if="selectedCount(list)"
                                        class="rounded-full bg-orange-500 px-2 py-0.5 text-xs font-semibold text-white"
                                    >
                                        {{ selectedCount(list) }}
                                    </span>
                                    <span class="text-lg leading-none text-slate-400">{{ openTrades.includes(trade) ? '−' : '+' }}</span>
                                </span>
                            </button>

                            <div
                                v-show="openTrades.includes(trade)"
                                class="grid grid-cols-1 gap-2 border-t border-slate-100 px-4 py-3 sm:grid-cols-2"
                            >
                                <label
                                    v-for="service in list"
                                    :key="service.id"
                                    class="flex cursor-pointer items-center gap-2 text-sm text-slate-700"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="form.service_ids.includes(service.id)"
                                        class="rounded border-slate-300 text-orange-500 focus:ring-orange-500"
                                        @change="toggleService(service.id)"
                                    />
                                    {{ service.name }}
                                    <span class="text-xs text-slate-500">· {{ Number(service.sale_price).toFixed(0) }} lei · {{ service.duration_minutes }} min</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="rounded-lg border border-slate-200 bg-white p-6">
                    <legend class="px-2 text-sm font-semibold text-slate-900">Când vrei să venim?</legend>

                    <div class="mt-2 rounded-md bg-slate-50 p-4 text-sm text-slate-700">
                        <p class="font-semibold text-slate-900">Estimare orientativă</p>
                        <p v-if="selectedServices.length" class="mt-1">
                            Manoperă: <strong>{{ totalPrice.toFixed(0) }} lei</strong> ·
                            Timp: <strong>{{ durationLabel }}</strong>
                        </p>
                        <p class="mt-1">
                            Deplasare:
                            <strong v-if="travelFee">{{ travelFee }} lei</strong>
                            <strong v-else>gratuită</strong>
                            <span v-if="travelFee" class="text-xs text-slate-500"> (se scade din deviz dacă ne încredințați lucrarea)</span>
                        </p>
                        <p v-if="selectedServices.length" class="mt-1 border-t border-slate-200 pt-1">
                            Total estimat: <strong>{{ grandTotal.toFixed(0) }} lei</strong>
                        </p>
                        <p v-if="travel?.free_above" class="mt-1 text-xs text-slate-500">
                            Deplasare gratuită pentru lucrări de peste {{ travel.free_above }} lei.
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Prețul final se confirmă în devizul scris. Materialele nu sunt incluse.
                        </p>
                    </div>

                    <p class="mt-4 text-sm text-slate-500">
                        Opțional. Dacă nu alegi o oră, te sunăm pentru a stabili împreună.
                    </p>

                    <p v-if="loadingDates" class="mt-3 text-sm text-slate-400">Se încarcă zilele disponibile...</p>
                    <p v-else-if="!availableDates.length" class="mt-3 text-sm text-slate-500">
                        Nu avem zile libere afișate acum. Trimite cererea și te sunăm.
                    </p>

                    <div v-else class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <button
                            v-for="d in availableDates"
                            :key="d"
                            type="button"
                            class="rounded-md border px-3 py-2 text-sm"
                            :class="selectedDate === d ? 'border-orange-500 bg-orange-50 font-semibold' : 'border-slate-200 hover:border-orange-300'"
                            @click="pickDate(d)"
                        >
                            {{ formatDate(d) }}
                        </button>
                    </div>

                    <div v-if="selectedDate" class="mt-4">
                        <p class="text-sm font-medium text-slate-700">Ora de începere</p>
                        <p v-if="loadingSlots" class="mt-2 text-sm text-slate-400">Se încarcă orele...</p>
                        <div v-else class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="t in slots"
                                :key="t"
                                type="button"
                                class="rounded-md border px-3 py-1.5 text-sm"
                                :class="form.scheduled_at === `${selectedDate} ${t}` ? 'border-orange-500 bg-orange-500 text-white' : 'border-slate-200 hover:border-orange-300'"
                                @click="pickSlot(t)"
                            >
                                {{ t }}
                            </button>
                        </div>
                    </div>

                    <p v-if="form.errors.scheduled_at" class="mt-2 text-sm text-red-600">{{ form.errors.scheduled_at }}</p>
                </fieldset>

                <fieldset class="rounded-lg border border-slate-200 bg-white p-6">
                    <legend class="px-2 text-sm font-semibold text-slate-900">Unde și cum</legend>

                    <div class="mt-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Nume *</label>
                            <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Telefon *</label>
                            <input v-model="form.phone" type="text" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Email</label>
                            <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Adresa lucrării *</label>
                            <input v-model="form.address" type="text" required placeholder="Strada, număr, bloc, scară, etaj" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Oraș</label>
                            <input v-model="form.city" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="form.errors.city" class="mt-1 text-sm text-red-600">{{ form.errors.city }}</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-slate-700">Cum se manifestă problema?</label>
                        <textarea
                            v-model="form.notes"
                            rows="4"
                            placeholder="Ex.: chiuveta pierde apă pe sub blat. Până unde se extinde problema, când a început și ce ați mai încercat."
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</p>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-slate-700">Fotografii (maximum 5)</label>
                        <input
                            type="file"
                            accept="image/*"
                            multiple
                            class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:rounded-md file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold"
                            @change="handleFiles"
                        />
                        <p v-if="form.errors.photos" class="mt-1 text-sm text-red-600">{{ form.errors.photos }}</p>

                        <div v-if="previews.length" class="mt-3 flex flex-wrap gap-2">
                            <div v-for="(preview, index) in previews" :key="preview" class="relative">
                                <img
                                    :src="preview"
                                    class="h-20 w-20 rounded-md border border-slate-200 object-cover"
                                    :alt="`Fotografie ${index + 1}`"
                                />
                                <button
                                    type="button"
                                    class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-white shadow"
                                    :aria-label="`Elimină fotografia ${index + 1}`"
                                    @click="removePhoto(index)"
                                >
                                    &times;
                                </button>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">Ați selectat {{ previews.length }} din {{ maxPhotos }} fotografii.</p>
                    </div>

                    <label class="mt-4 flex items-start gap-2 text-sm text-slate-600">
                        <input v-model="form.privacy_consent" type="checkbox" required class="mt-1 rounded border-slate-300 text-orange-500 focus:ring-orange-500" />
                        <span>
                            Sunt de acord cu prelucrarea datelor personale în conformitate cu
                            <a :href="route('public.privacy')" class="text-blue-600 underline">Politica de confidențialitate</a>
                            și <a :href="route('public.terms')" class="text-blue-600 underline">Termenii și condițiile</a> de utilizare.
                        </span>
                    </label>
                    <p v-if="form.errors.privacy_consent" class="mt-1 text-sm text-red-600">{{ form.errors.privacy_consent }}</p>
                </fieldset>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-md bg-orange-500 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-orange-400 disabled:opacity-50"
                >
                    {{ form.processing ? 'Se trimite...' : 'Trimite cererea de deviz' }}
                </button>

                <p class="text-center text-xs text-slate-400">
                    Fără niciun angajament. Devizul se întocmește după constatarea problemei la locul lucrării.
                </p>
            </form>
        </section>
    </PublicLayout>
</template>