<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    reviews: Object,
    filters: Object,
    statuses: Object,
    summary: Object,
});

const statusClasses = {
    pending: 'bg-slate-100 text-slate-600',
    submitted: 'bg-amber-100 text-amber-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-700',
};

const filterTabs = computed(() => [
    { value: '', label: 'Toate' },
    ...Object.entries(props.statuses).map(([value, label]) => ({ value, label })),
]);

function applyFilter(status) {
    router.get(route('admin.reviews.index'), status ? { status } : {}, { preserveState: true, replace: true });
}

function approve(review) {
    router.patch(route('admin.reviews.approve', review.id), {}, { preserveScroll: true });
}

function reject(review) {
    if (confirm('Respingi această recenzie? Nu va apărea pe site.')) {
        router.patch(route('admin.reviews.reject', review.id), {}, { preserveScroll: true });
    }
}

function destroy(review) {
    if (confirm('Ștergi definitiv această recenzie?')) {
        router.delete(route('admin.reviews.destroy', review.id), { preserveScroll: true });
    }
}

function stars(rating) {
    const value = Number(rating) || 0;
    return '★'.repeat(value) + '☆'.repeat(Math.max(0, 5 - value));
}

const editing = ref(null);
const form = useForm({ author_name: '', city: '', service_label: '', rating: 5, comment: '' });

function openEdit(review) {
    editing.value = review;
    form.author_name = review.author_name ?? '';
    form.city = review.city ?? '';
    form.service_label = review.service_label ?? '';
    form.rating = review.rating ?? 5;
    form.comment = review.comment ?? '';
}

function submitEdit() {
    form.put(route('admin.reviews.update', editing.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = null;
        },
    });
}
</script>

<template>
    <Head title="Recenzii clienti" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Recenzii clienti</h2>
                <div class="flex gap-4 text-sm text-slate-500">
                    <span>În așteptare: <strong class="text-amber-700">{{ summary.pending }}</strong></span>
                    <span>Publicate: <strong class="text-green-700">{{ summary.approved }}</strong></span>
                    <span v-if="summary.average">Media: <strong class="text-amber-600">{{ summary.average }} ★</strong></span>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="mb-4 flex flex-wrap gap-2">
                    <button
                        v-for="tab in filterTabs"
                        :key="tab.value"
                        type="button"
                        class="rounded-full border px-3 py-1.5 text-sm font-medium transition"
                        :class="(filters.status ?? '') === tab.value ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                        @click="applyFilter(tab.value)"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <div class="space-y-4">
                    <div v-for="review in reviews.data" :key="review.id" class="rounded-lg bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-3">
                                    <span class="text-lg font-semibold text-slate-800">{{ review.author_name || review.client?.name || 'Client' }}</span>
                                    <span class="text-lg text-amber-500">{{ stars(review.rating) }}</span>
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusClasses[review.status]">
                                        {{ statuses[review.status] }}
                                    </span>
                                </div>
                                <div class="mt-1 text-xs text-slate-400">
                                    <span v-if="review.service_label">{{ review.service_label }}</span>
                                    <span v-if="review.city"> · {{ review.city }}</span>
                                    <span v-if="review.submitted_at"> · {{ new Date(review.submitted_at).toLocaleDateString('ro-RO') }}</span>
                                    <span v-if="review.consent_to_publish" class="text-green-600"> · consimțământ publicare</span>
                                    <span v-else class="text-red-500"> · fără consimțământ</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 text-sm">
                                <button v-if="review.status !== 'approved'" class="rounded-md bg-green-600 px-3 py-1.5 font-semibold text-white hover:bg-green-500" @click="approve(review)">
                                    Publică
                                </button>
                                <button v-if="review.status !== 'rejected'" class="rounded-md border border-slate-300 px-3 py-1.5 font-medium text-slate-600 hover:bg-slate-50" @click="reject(review)">
                                    Respinge
                                </button>
                                <button class="rounded-md border border-slate-300 px-3 py-1.5 font-medium text-slate-600 hover:bg-slate-50" @click="openEdit(review)">
                                    Editează
                                </button>
                                <button class="rounded-md border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50" @click="destroy(review)">
                                    Șterge
                                </button>
                            </div>
                        </div>
                        <p v-if="review.comment" class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ review.comment }}</p>
                        <p v-else class="mt-3 text-sm italic text-slate-400">Fără comentariu.</p>
                    </div>

                    <div v-if="!reviews.data.length" class="rounded-lg bg-white p-10 text-center text-sm text-slate-400 shadow-sm">
                        Nicio recenzie în această categorie.
                    </div>
                </div>

                <div v-if="reviews.links.length > 3" class="mt-4 flex flex-wrap gap-2">
                    <Link
                        v-for="link in reviews.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        :class="[
                            'rounded-md px-3 py-1.5 text-sm',
                            link.active ? 'bg-blue-600 text-white' : 'bg-white text-slate-500 hover:bg-slate-100',
                            !link.url ? 'pointer-events-none opacity-40' : '',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <Modal :show="editing !== null" max-width="lg" @close="editing = null">
            <div v-if="editing" class="p-6">
                <h3 class="text-lg font-semibold text-slate-800">Editează recenzia</h3>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-600">Nume afișat</label>
                        <input v-model="form.author_name" type="text" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Oraș</label>
                        <input v-model="form.city" type="text" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Lucrare</label>
                        <input v-model="form.service_label" type="text" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Notă (1-5)</label>
                        <input v-model.number="form.rating" type="number" min="1" max="5" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium text-slate-600">Comentariu</label>
                        <textarea v-model="form.comment" rows="4" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="editing = null">Anulează</button>
                    <button type="button" :disabled="form.processing" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50" @click="submitEdit">Salvează</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
