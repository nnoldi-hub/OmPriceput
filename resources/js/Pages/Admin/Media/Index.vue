<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ media: Object });
const page = usePage();

const uploading = ref(false);
const error = ref('');
const copied = ref(null);

function onUpload(event) {
    const files = Array.from(event.target.files || []);
    if (!files.length) return;

    uploading.value = true;
    error.value = '';

    const data = new FormData();
    files.forEach((file) => data.append('files[]', file));

    window.axios
        .post(route('admin.media.store'), data)
        .then(() => router.reload({ preserveScroll: true }))
        .catch(() => {
            error.value = 'Incarcarea a esuat. Verifica fisierele (imagini, max 8MB).';
        })
        .finally(() => {
            uploading.value = false;
            event.target.value = '';
        });
}

function save(item) {
    router.put(route('admin.media.update', item.id), { alt: item.alt, title: item.title }, { preserveScroll: true, preserveState: true });
}

function destroy(item) {
    if (confirm(`Stergi imaginea "${item.original_name}"?`)) {
        router.delete(route('admin.media.destroy', item.id), { preserveScroll: true });
    }
}

function copy(item) {
    navigator.clipboard?.writeText(item.url);
    copied.value = item.id;
    setTimeout(() => (copied.value = null), 1500);
}
</script>

<template>
    <Head title="Biblioteca media" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Biblioteca media</h2>
                <Link :href="route('admin.blog.index')" class="text-sm text-slate-500 hover:text-slate-700">Înapoi la articole</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="page.props.flash.success" class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ page.props.flash.success }}</div>
                <div v-if="error" class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ error }}</div>

                <label
                    class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-300 bg-white p-8 text-center hover:border-blue-400"
                >
                    <span class="text-sm font-semibold text-slate-700">{{ uploading ? 'Se incarca...' : 'Trage imagini aici sau click pentru a incarca' }}</span>
                    <span class="mt-1 text-xs text-slate-400">JPG, PNG, WebP, GIF, SVG — max 8MB per fisier</span>
                    <input type="file" accept="image/*" multiple class="hidden" :disabled="uploading" @change="onUpload" />
                </label>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <div v-for="item in media.data" :key="item.id" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        <img :src="item.url" :alt="item.alt || item.original_name" class="h-40 w-full object-cover" loading="lazy" />
                        <div class="space-y-2 p-3">
                            <p class="truncate text-xs text-slate-400" :title="item.original_name">{{ item.original_name }}</p>
                            <input v-model="item.alt" placeholder="Text alt" class="block w-full rounded-md border-slate-300 text-xs" />
                            <input v-model="item.title" placeholder="Titlu" class="block w-full rounded-md border-slate-300 text-xs" />
                            <div class="flex items-center justify-between pt-1 text-xs">
                                <button type="button" class="font-medium text-blue-600 hover:text-blue-500" @click="save(item)">Salveaza</button>
                                <button type="button" class="font-medium text-slate-500 hover:text-slate-700" @click="copy(item)">{{ copied === item.id ? 'Copiat!' : 'Copiaza URL' }}</button>
                                <button type="button" class="font-medium text-red-600 hover:text-red-500" @click="destroy(item)">Sterge</button>
                            </div>
                        </div>
                    </div>
                </div>

                <p v-if="!media.data.length" class="py-10 text-center text-sm text-slate-400">Biblioteca este goala.</p>

                <div v-if="media.links?.length > 3" class="flex flex-wrap items-center justify-center gap-1">
                    <template v-for="link in media.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            class="rounded-md border px-3 py-1 text-sm"
                            :class="link.active ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 text-slate-600 hover:bg-slate-50'"
                            v-html="link.label"
                        />
                        <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
