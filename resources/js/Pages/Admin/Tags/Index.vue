<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

defineProps({ tags: Array });
const page = usePage();
const form = useForm({ name: '' });

function add() {
    form.post(route('admin.blog.tags.store'), { onSuccess: () => form.reset() });
}
function update(tag) {
    router.put(route('admin.blog.tags.update', tag.id), { name: tag.name, slug: tag.slug }, { preserveScroll: true });
}
function destroy(tag) {
    if (confirm(`Ștergi tag-ul "${tag.name}"?`)) {
        router.delete(route('admin.blog.tags.destroy', tag.id));
    }
}
</script>

<template>
    <Head title="Tag-uri blog" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Tag-uri blog</h2>
                <Link :href="route('admin.blog.index')" class="text-sm text-slate-500 hover:text-slate-700">Înapoi la articole</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="page.props.flash.success" class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ page.props.flash.success }}</div>

                <form class="flex gap-3 rounded-lg bg-white p-5 shadow-sm" @submit.prevent="add">
                    <input v-model="form.name" placeholder="Nume tag (ex: polizor unghiular)" class="flex-1 rounded-md border-slate-300" />
                    <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Adaugă tag</button>
                </form>
                <p v-if="form.errors.name" class="-mt-4 text-xs text-red-600">{{ form.errors.name }}</p>

                <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs uppercase text-slate-500">Nume</th>
                                <th class="px-3 py-3 text-left text-xs uppercase text-slate-500">Slug</th>
                                <th class="px-3 py-3 text-left text-xs uppercase text-slate-500">Articole</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="tag in tags" :key="tag.id">
                                <td class="px-3 py-3"><input v-model="tag.name" class="w-full rounded border-slate-300 text-sm" /></td>
                                <td class="px-3 py-3"><input v-model="tag.slug" class="w-full rounded border-slate-300 text-sm" /></td>
                                <td class="px-3 py-3 text-sm text-slate-500">{{ tag.posts_count }}</td>
                                <td class="px-3 py-3 text-right text-sm">
                                    <button class="text-blue-600" @click="update(tag)">Salvează</button>
                                    <button class="ml-3 text-red-600" @click="destroy(tag)">Șterge</button>
                                </td>
                            </tr>
                            <tr v-if="!tags.length">
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-400">Niciun tag.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
