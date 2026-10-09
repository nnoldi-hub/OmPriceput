<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({ categories: Array });
const page = usePage();
const form = useForm({ name: '', description: '', parent_id: '', sort_order: 0 });

function add() {
    form.post(route('admin.blog.categories.store'), { onSuccess: () => form.reset() });
}
function update(category) {
    router.put(route('admin.blog.categories.update', category.id), {
        name: category.name,
        slug: category.slug,
        description: category.description,
        parent_id: category.parent_id,
        sort_order: category.sort_order,
    }, { preserveScroll: true });
}
function destroy(category) {
    if (confirm(`Ștergi categoria "${category.name}"? Articolele rămân, dar fără această categorie.`)) {
        router.delete(route('admin.blog.categories.destroy', category.id));
    }
}
function parents(excludeId) {
    return props.categories.filter((category) => category.id !== excludeId);
}
</script>

<template>
    <Head title="Categorii blog" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Categorii blog</h2>
                <Link :href="route('admin.blog.index')" class="text-sm text-slate-500 hover:text-slate-700">Înapoi la articole</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="page.props.flash.success" class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ page.props.flash.success }}</div>

                <form class="grid gap-3 rounded-lg bg-white p-5 shadow-sm sm:grid-cols-4" @submit.prevent="add">
                    <input v-model="form.name" placeholder="Nume categorie" class="rounded-md border-slate-300 sm:col-span-1" />
                    <input v-model="form.description" placeholder="Descriere (opțional)" class="rounded-md border-slate-300 sm:col-span-1" />
                    <select v-model="form.parent_id" class="rounded-md border-slate-300 sm:col-span-1">
                        <option value="">Fără părinte</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                    <button class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white sm:col-span-1">Adaugă categorie</button>
                    <p v-if="form.errors.name" class="text-xs text-red-600 sm:col-span-4">{{ form.errors.name }}</p>
                </form>

                <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs uppercase text-slate-500">Nume</th>
                                <th class="px-3 py-3 text-left text-xs uppercase text-slate-500">Slug</th>
                                <th class="px-3 py-3 text-left text-xs uppercase text-slate-500">Descriere</th>
                                <th class="px-3 py-3 text-left text-xs uppercase text-slate-500">Părinte</th>
                                <th class="px-3 py-3 text-left text-xs uppercase text-slate-500">Articole</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="category in categories" :key="category.id">
                                <td class="px-3 py-3"><input v-model="category.name" class="w-full rounded border-slate-300 text-sm" /></td>
                                <td class="px-3 py-3"><input v-model="category.slug" class="w-full rounded border-slate-300 text-sm" /></td>
                                <td class="px-3 py-3"><input v-model="category.description" class="w-full rounded border-slate-300 text-sm" /></td>
                                <td class="px-3 py-3">
                                    <select v-model="category.parent_id" class="w-full rounded border-slate-300 text-sm">
                                        <option :value="null">—</option>
                                        <option v-for="option in parents(category.id)" :key="option.id" :value="option.id">{{ option.name }}</option>
                                    </select>
                                </td>
                                <td class="px-3 py-3 text-sm text-slate-500">{{ category.posts_count }}</td>
                                <td class="px-3 py-3 text-right text-sm">
                                    <button class="text-blue-600" @click="update(category)">Salvează</button>
                                    <button class="ml-3 text-red-600" @click="destroy(category)">Șterge</button>
                                </td>
                            </tr>
                            <tr v-if="!categories.length">
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-400">Nicio categorie.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
