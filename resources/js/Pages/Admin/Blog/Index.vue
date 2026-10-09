<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

defineProps({ posts: Object });
const page = usePage();
function destroy(post) { if (confirm(`Ștergi articolul "${post.title}"?`)) router.delete(route('admin.blog.destroy', post.id)); }
function formatDate(value) { return value ? new Date(value).toLocaleDateString('ro-RO') : '—'; }
</script>

<template>
    <Head title="Blog" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Articole blog</h2>
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.blog.categories.index')" class="text-sm font-semibold text-slate-600 hover:text-slate-800">Categorii</Link>
                    <Link :href="route('admin.blog.tags.index')" class="text-sm font-semibold text-slate-600 hover:text-slate-800">Tag-uri</Link>
                    <Link :href="route('admin.blog.create')" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white">Articol nou</Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-4 sm:px-6 lg:px-8">
                <div v-if="page.props.flash.success" class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ page.props.flash.success }}</div>

                <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Titlu</th>
                                <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Categorii</th>
                                <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Autor</th>
                                <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Publicat</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="post in posts.data" :key="post.id">
                                <td class="px-4 py-3 font-medium">{{ post.title }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500">
                                    <span v-if="post.categories?.length">{{ post.categories.map((c) => c.name).join(', ') }}</span>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ post.author?.name || '—' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="post.status === 'published' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'">
                                        {{ post.status === 'published' ? 'Publicat' : 'Ciornă' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ formatDate(post.published_at) }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <Link :href="route('admin.blog.edit', post.id)" class="text-blue-600">Editează</Link>
                                    <button class="ml-3 text-red-600" @click="destroy(post)">Șterge</button>
                                </td>
                            </tr>
                            <tr v-if="!posts.data.length">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">Niciun articol încă.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="posts.links.length > 3" class="flex flex-wrap justify-center gap-2">
                    <Link
                        v-for="link in posts.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        :class="[
                            'rounded-md px-3 py-1.5 text-sm',
                            link.active ? 'bg-blue-600 text-white' : 'text-slate-500 hover:bg-slate-100',
                            !link.url ? 'pointer-events-none opacity-40' : '',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
