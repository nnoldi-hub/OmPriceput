<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    multiple: { type: Boolean, default: false },
    selected: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'select']);

const items = ref([]);
const chosen = ref([]);
const loading = ref(false);
const uploading = ref(false);
const error = ref('');

watch(
    () => props.show,
    (show) => {
        if (show) {
            chosen.value = [...props.selected];
            error.value = '';
            load();
        }
    },
);

async function load() {
    loading.value = true;
    try {
        const { data } = await window.axios.get(route('admin.media.json'));
        items.value = data.data;
    } catch (e) {
        error.value = 'Nu am putut incarca biblioteca media.';
    } finally {
        loading.value = false;
    }
}

function isChosen(id) {
    return chosen.value.includes(id);
}

function toggle(item) {
    if (!props.multiple) {
        chosen.value = [item.id];
        return;
    }

    const index = chosen.value.indexOf(item.id);
    if (index === -1) chosen.value.push(item.id);
    else chosen.value.splice(index, 1);
}

function confirm() {
    const picked = items.value.filter((item) => chosen.value.includes(item.id));
    emit('select', picked);
    emit('close');
}

async function upload(event) {
    const files = Array.from(event.target.files || []);
    if (!files.length) return;

    uploading.value = true;
    error.value = '';

    const data = new FormData();
    files.forEach((file) => data.append('files[]', file));

    try {
        await window.axios.post(route('admin.media.store'), data);
        await load();
    } catch (e) {
        error.value = 'Incarcarea a esuat. Verifica fisierele (imagini, max 8MB).';
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
}

const selectedCount = computed(() => chosen.value.length);
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" @click.self="emit('close')">
        <div class="flex h-[80vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                <h3 class="text-base font-semibold text-slate-900">Biblioteca media</h3>
                <div class="flex items-center gap-3">
                    <label class="cursor-pointer rounded-md bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200">
                        {{ uploading ? 'Se incarca...' : '+ Incarca' }}
                        <input type="file" accept="image/*" multiple class="hidden" :disabled="uploading" @change="upload" />
                    </label>
                    <button type="button" class="text-slate-400 hover:text-slate-700" @click="emit('close')">&times;</button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-5">
                <p v-if="error" class="mb-3 rounded-md bg-red-50 p-2 text-sm text-red-700">{{ error }}</p>
                <p v-if="loading" class="py-10 text-center text-sm text-slate-400">Se incarca...</p>
                <p v-else-if="!items.length" class="py-10 text-center text-sm text-slate-400">Nicio imagine. Incarca una mai sus.</p>

                <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-5">
                    <button
                        v-for="item in items"
                        :key="item.id"
                        type="button"
                        class="group relative overflow-hidden rounded-md border-2 bg-slate-50 text-left"
                        :class="isChosen(item.id) ? 'border-blue-600 ring-2 ring-blue-200' : 'border-transparent hover:border-slate-300'"
                        @click="toggle(item)"
                    >
                        <img :src="item.url" :alt="item.alt || item.original_name" class="h-24 w-full object-cover" loading="lazy" />
                        <span class="block truncate px-2 py-1 text-[11px] text-slate-500">{{ item.original_name }}</span>
                        <span
                            v-if="isChosen(item.id)"
                            class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white"
                        >&#10003;</span>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-slate-200 px-5 py-3">
                <span class="text-xs text-slate-400">{{ selectedCount }} selectate{{ multiple ? '' : ' (selectie unica)' }}</span>
                <div class="flex items-center gap-2">
                    <button type="button" class="rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="emit('close')">Anuleaza</button>
                    <button type="button" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50" :disabled="!selectedCount" @click="confirm">
                        {{ multiple ? 'Adauga selectate' : 'Selecteaza' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
