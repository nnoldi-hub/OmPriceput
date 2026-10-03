<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    page: Object,
    types: Object,
    trades: Object,
    services: Array,
});

const page = usePage();

const form = useForm({
    job_type: 'instalare',
    service_ids: [],
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
        },
    });
}
</script>

<template>
    <SeoHead
        title="Cere deviz - Omul Potrivit"
        description="Trimite cererea de deviz: alese tipul lucrarii, trimite cateva poze si primesti devizul scris dupa constatarea la fata locului. Constatarea este gratuita."
    />

    <PublicLayout>
        <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="font-display text-3xl font-bold text-slate-900">Cerere de deviz</h1>
                <p class="mx-auto mt-3 max-w-2xl text-slate-500">
                    Spune-ne ce ai nevoie si trimite cateva poze. Venim la constatare si iti lasam devizul scris,
                    cu materialele si manopera exacte. Constatarea este gratuita daca executam lucrarea.
                </p>
            </div>

            <ol class="mt-8 grid grid-cols-1 gap-4 text-sm sm:grid-cols-4">
                <li class="rounded-lg border border-slate-200 bg-white p-4">
                    <span class="font-semibold text-orange-500">1.</span>
                    Trimite cererea
                    <p class="mt-1 text-slate-500">Tip lucrare, poze, adresa.</p>
                </li>
                <li class="rounded-lg border border-slate-200 bg-white p-4">
                    <span class="font-semibold text-orange-500">2.</span>
                    Confirmam ora
                    <p class="mt-1 text-slate-500">Iti sunam in cel mult o zi lucratoare.</p>
                </li>
                <li class="rounded-lg border border-slate-200 bg-white p-4">
                    <span class="font-semibold text-orange-500">3.</span>
                    Constatare la loc
                    <p class="mt-1 text-slate-500">Vedem problema, stabilim materialele.</p>
                </li>
                <li class="rounded-lg border border-slate-200 bg-white p-4">
                    <span class="font-semibold text-orange-500">4.</span>
                    Deviz scris
                    <p class="mt-1 text-slate-500">Il primesti in portal si decizi fara grabire.</p>
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
                    <legend class="px-2 text-sm font-semibold text-slate-900">Ce ai nevoie?</legend>

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
                                    Vedem problema fara sa schimbam nimic.
                                </span>
                                <span v-else-if="value === 'urgenta'" class="block text-xs text-slate-500">
                                    Luam lucrarea in ziua cererii, daca programul permite.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div v-if="services.length" class="mt-6">
                        <p class="text-sm font-medium text-slate-700">Ce lucrari te intereseaza?</p>
                        <p class="text-xs text-slate-500">Optional. Ne ajuta sa aducem sculele potrivite.</p>

                        <div v-for="(list, trade) in servicesByTrade" :key="trade" class="mt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                {{ trades[trade] ?? trade }}
                            </p>
                            <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
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
                                    <span class="text-xs text-slate-400">({{ service.unit }})</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="rounded-lg border border-slate-200 bg-white p-6">
                    <legend class="px-2 text-sm font-semibold text-slate-900">Unde si cand</legend>

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
                            <label class="block text-sm font-medium text-slate-700">Adresa lucrarii *</label>
                            <input v-model="form.address" type="text" required placeholder="Strada, numar, bloc, scara, etaj" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Oras</label>
                            <input v-model="form.city" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="form.errors.city" class="mt-1 text-sm text-red-600">{{ form.errors.city }}</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-slate-700">Cum arata problema?</label>
                        <textarea
                            v-model="form.notes"
                            rows="4"
                            placeholder="Ex: chiuvita scurge, la doua etaje. Pana unde merge problema, cand a inceput, ce ati mai incercat."
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</p>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-slate-700">Poze (maxim 5)</label>
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
                                    :alt="`Foto ${index + 1}`"
                                />
                                <button
                                    type="button"
                                    class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-white shadow"
                                    :aria-label="`Elimina foto ${index + 1}`"
                                    @click="removePhoto(index)"
                                >
                                    &times;
                                </button>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">Ai selectat {{ previews.length }} din {{ maxPhotos }} poze.</p>
                    </div>

                    <label class="mt-4 flex items-start gap-2 text-sm text-slate-600">
                        <input v-model="form.privacy_consent" type="checkbox" required class="mt-1 rounded border-slate-300 text-orange-500 focus:ring-orange-500" />
                        <span>
                            Sunt de acord cu prelucrarea datelor conform
                            <a :href="route('public.privacy')" class="text-blue-600 underline">Politicii de confidentialitate</a>
                            si <a :href="route('public.terms')" class="text-blue-600 underline">Termenilor</a>.
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
                    Fara angajament. Devizul se intocmeste dupa ce am vazut problema la locul lucrarii.
                </p>
            </form>
        </section>
    </PublicLayout>
</template>