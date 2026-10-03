<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ page: Object });
const initialContent = props.page.content || {};
const sectionTypes = [
    { value: 'hero', label: 'Hero' },
    { value: 'stats', label: 'Statistici' },
    { value: 'packages', label: 'Pachete de intretinere' },
    { value: 'process', label: 'Cum lucram' },
    { value: 'blog', label: 'Ultimele articole' },
    { value: 'cta', label: 'CTA' },
    { value: 'text_image', label: 'Text + imagine' },
    { value: 'gallery', label: 'Galerie imagini' },
    { value: 'benefits', label: 'Lista beneficii' },
    { value: 'html', label: 'HTML custom' },
];
const defaults = {
    hero: { title: '', subtitle: '', text: '', button_text: '', button_link: '', image: '' },
    stats: { items: [] },
    packages: { items: [] },
    process: { items: [] },
    blog: { limit: 3 },
    cta: { title: '', text: '', button_text: '', button_link: '/contact' },
    text_image: { title: '', text: '', image: '', image_alt: '' },
    gallery: { images: [] },
    benefits: { title: '', items: [] },
    html: { html: '' },
};
function sectionContent(section) {
    if (section.content && typeof section.content === 'object') return section.content;
    try { return section.content ? JSON.parse(section.content) : { ...defaults[section.section_key] }; } catch { return { ...defaults[section.section_key] }; }
}
const form = useForm({
    title: props.page.title,
    subtitle: props.page.subtitle || '',
    seo_title: props.page.seo_title || '',
    seo_description: props.page.seo_description || '',
    status: props.page.status,
    content: props.page.content ? JSON.stringify(props.page.content, null, 2) : '',
    sections: (props.page.sections || []).map((section) => ({
        section_key: section.section_key,
        content: sectionContent(section),
        sort_order: section.sort_order,
    })),
});
const visual = {
    hero_title: initialContent.hero_title || '',
    intro: initialContent.intro || '',
    body_html: initialContent.body_html || '',
    cta_label: initialContent.cta_label || '',
    cta_url: initialContent.cta_url || '',
};
function syncVisualContent() {
    let advanced = {};
    try {
        advanced = form.content ? JSON.parse(form.content) : {};
    } catch {
        advanced = {};
    }
    form.content = JSON.stringify({
        ...advanced,
        hero_title: visual.hero_title,
        intro: visual.intro,
        body_html: visual.body_html,
        cta_label: visual.cta_label,
        cta_url: visual.cta_url,
    }, null, 2);
}
function format(command, value = null) {
    document.execCommand(command, false, value);
    visual.body_html = document.querySelector('[contenteditable="true"]')?.innerHTML || visual.body_html;
}
function addSection() { form.sections.push({ section_key: 'hero', content: { ...defaults.hero }, sort_order: form.sections.length }); }
function removeSection(index) { form.sections.splice(index, 1); }
function changeType(section) { section.content = { ...(defaults[section.section_key] || {}) }; }
function moveSection(index, direction) {
    const target = index + direction;
    if (target < 0 || target >= form.sections.length) return;
    [form.sections[index], form.sections[target]] = [form.sections[target], form.sections[index]];
    form.sections.forEach((section, order) => { section.sort_order = order; });
}
function addListItem(section, key, value = '') { if (!Array.isArray(section.content[key])) section.content[key] = []; section.content[key].push(value); }
function removeListItem(section, key, index) { section.content[key].splice(index, 1); }
function submit() { syncVisualContent(); form.put(route('admin.pages.update', props.page.id)); }
</script>

<template>
    <Head :title="`Editeaza ${page.title}`" />
    <AuthenticatedLayout>
        <template #header><div class="flex items-center justify-between"><h2 class="text-xl font-semibold text-gray-800">Editeaza pagina: {{ page.title }}</h2><Link :href="route('admin.pages.preview', page.id)" target="_blank" class="text-sm font-semibold text-blue-600">Deschide preview</Link></div></template>
        <div class="py-8"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8"><form class="space-y-6 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="text-sm font-medium text-slate-700">Titlu<input v-model="form.title" class="mt-1 block w-full rounded-md border-slate-300" /></label>
                <label class="text-sm font-medium text-slate-700">Subtitlu<input v-model="form.subtitle" class="mt-1 block w-full rounded-md border-slate-300" /></label>
                <label class="text-sm font-medium text-slate-700">SEO title<input v-model="form.seo_title" class="mt-1 block w-full rounded-md border-slate-300" /></label>
                <label class="text-sm font-medium text-slate-700">Status<select v-model="form.status" class="mt-1 block w-full rounded-md border-slate-300"><option value="draft">Ciorna</option><option value="published">Publicata</option></select></label>
            </div>
            <label class="block text-sm font-medium text-slate-700">Descriere SEO<textarea v-model="form.seo_description" rows="2" class="mt-1 block w-full rounded-md border-slate-300" /></label>
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="space-y-4">
                    <h3 class="font-semibold text-slate-900">Editor vizual</h3>
                    <label class="block text-sm font-medium text-slate-700">Titlu principal<input v-model="visual.hero_title" class="mt-1 block w-full rounded-md border-slate-300" /></label>
                    <label class="block text-sm font-medium text-slate-700">Introducere<textarea v-model="visual.intro" rows="3" class="mt-1 block w-full rounded-md border-slate-300" /></label>
                    <div><div class="mb-2 flex gap-2"><button type="button" class="rounded border px-2 py-1 text-xs font-bold" @click="format('bold')">B</button><button type="button" class="rounded border px-2 py-1 text-xs italic" @click="format('italic')">I</button><button type="button" class="rounded border px-2 py-1 text-xs" @click="format('insertUnorderedList')">Lista</button></div><div contenteditable="true" class="min-h-40 rounded-md border border-slate-300 p-3 text-sm outline-none focus:border-blue-500" @input="visual.body_html = $event.target.innerHTML" v-html="visual.body_html"></div></div>
                    <div class="grid gap-3 sm:grid-cols-2"><input v-model="visual.cta_label" placeholder="Text buton" class="rounded-md border-slate-300" /><input v-model="visual.cta_url" placeholder="/contact" class="rounded-md border-slate-300" /></div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-5"><h3 class="font-semibold text-slate-900">Preview live</h3><div class="mt-4 rounded-lg bg-white p-6 shadow-sm"><h1 class="text-2xl font-bold text-slate-900">{{ visual.hero_title || form.title }}</h1><p v-if="visual.intro" class="mt-3 text-slate-600">{{ visual.intro }}</p><div v-if="visual.body_html" class="prose prose-sm mt-5 max-w-none text-slate-600" v-html="visual.body_html"></div><a v-if="visual.cta_label" :href="visual.cta_url || '#'" class="mt-5 inline-block rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white">{{ visual.cta_label }}</a></div></div>
            </div>
            <label class="block text-sm font-medium text-slate-700">Continut JSON avansat<textarea v-model="form.content" rows="6" class="mt-1 block w-full rounded-md border-slate-300 font-mono text-sm" placeholder='{"hero_title":"..."}' /></label>
            <div class="border-t pt-5"><div class="mb-3 flex items-center justify-between"><h3 class="font-semibold">Sectiuni dinamice</h3><button type="button" class="text-sm text-blue-600" @click="addSection">+ Adauga sectiune</button></div>
                <div v-for="(section, index) in form.sections" :key="index" class="mb-4 rounded-md border border-slate-200 p-4">
                    <div class="flex flex-wrap items-center gap-3"><span class="cursor-move text-slate-400" title="Ordine sectiune">☷</span><select v-model="section.section_key" class="rounded-md border-slate-300" @change="changeType(section)"><option v-for="type in sectionTypes" :key="type.value" :value="type.value">{{ type.label }}</option></select><button type="button" class="rounded border px-2 py-1 text-xs" :disabled="index === 0" @click="moveSection(index, -1)">Sus</button><button type="button" class="rounded border px-2 py-1 text-xs" :disabled="index === form.sections.length - 1" @click="moveSection(index, 1)">Jos</button><button type="button" class="ml-auto text-red-600" @click="removeSection(index)">Sterge sectiunea</button></div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <template v-if="section.section_key === 'hero'"><input v-model="section.content.title" placeholder="Titlu" class="rounded-md border-slate-300" /><input v-model="section.content.subtitle" placeholder="Subtitlu" class="rounded-md border-slate-300" /><textarea v-model="section.content.text" placeholder="Text" rows="2" class="rounded-md border-slate-300 sm:col-span-2" /><input v-model="section.content.button_text" placeholder="Text buton" class="rounded-md border-slate-300" /><input v-model="section.content.button_link" placeholder="/contact" class="rounded-md border-slate-300" /><input v-model="section.content.image" placeholder="/storage/hero.jpg" class="rounded-md border-slate-300 sm:col-span-2" /></template>
                        <template v-else-if="section.section_key === 'stats'"><div class="sm:col-span-2"><div v-for="(item, itemIndex) in section.content.items" :key="itemIndex" class="mb-2 flex gap-2"><input v-model="item.value" placeholder="500+" class="w-32 rounded-md border-slate-300" /><input v-model="item.label" placeholder="Instalari" class="flex-1 rounded-md border-slate-300" /><button type="button" class="text-red-600" @click="removeListItem(section, 'items', itemIndex)">×</button></div><button type="button" class="text-sm text-blue-600" @click="addListItem(section, 'items', { value: '', label: '' })">+ Adauga statistica</button></div></template>
                        <template v-else-if="section.section_key === 'packages'"><label class="text-sm text-slate-600 sm:col-span-2">ID-uri pachete, separate prin virgula<input :value="section.content.items.join(',')" class="mt-1 block w-full rounded-md border-slate-300" @input="section.content.items = $event.target.value.split(',').map((value) => Number(value.trim())).filter(Boolean)" /></label></template>
                        <template v-else-if="section.section_key === 'process' || section.section_key === 'benefits'"><input v-model="section.content.title" placeholder="Titlu sectiune" class="rounded-md border-slate-300 sm:col-span-2" /><div class="sm:col-span-2"><div v-for="(item, itemIndex) in section.content.items" :key="itemIndex" class="mb-2 flex gap-2"><input v-model="section.content.items[itemIndex]" placeholder="Element" class="flex-1 rounded-md border-slate-300" /><button type="button" class="text-red-600" @click="removeListItem(section, 'items', itemIndex)">×</button></div><button type="button" class="text-sm text-blue-600" @click="addListItem(section, 'items')">+ Adauga element</button></div></template>
                        <template v-else-if="section.section_key === 'blog'"><label class="text-sm text-slate-600">Numar articole<input v-model.number="section.content.limit" type="number" min="1" max="12" class="mt-1 block w-full rounded-md border-slate-300" /></label></template>
                        <template v-else-if="section.section_key === 'cta'"><input v-model="section.content.title" placeholder="Titlu" class="rounded-md border-slate-300" /><input v-model="section.content.button_text" placeholder="Text buton" class="rounded-md border-slate-300" /><textarea v-model="section.content.text" placeholder="Text" rows="2" class="rounded-md border-slate-300 sm:col-span-2" /><input v-model="section.content.button_link" placeholder="/contact" class="rounded-md border-slate-300 sm:col-span-2" /></template>
                        <template v-else-if="section.section_key === 'text_image'"><input v-model="section.content.title" placeholder="Titlu" class="rounded-md border-slate-300" /><input v-model="section.content.image" placeholder="/storage/imagine.jpg" class="rounded-md border-slate-300" /><textarea v-model="section.content.text" placeholder="Text" rows="3" class="rounded-md border-slate-300 sm:col-span-2" /><input v-model="section.content.image_alt" placeholder="Descriere imagine" class="rounded-md border-slate-300 sm:col-span-2" /></template>
                        <template v-else-if="section.section_key === 'gallery'"><label class="text-sm text-slate-600 sm:col-span-2">Imagini, câte una pe rând<textarea :value="section.content.images.join('\n')" rows="4" class="mt-1 block w-full rounded-md border-slate-300" @input="section.content.images = $event.target.value.split('\n').map((value) => value.trim()).filter(Boolean)" /></label></template>
                        <template v-else-if="section.section_key === 'html'"><textarea v-model="section.content.html" rows="8" placeholder="<div>HTML...</div>" class="rounded-md border-slate-300 font-mono text-sm sm:col-span-2" /></template>
                    </div>
                </div>
            </div>
            <div class="flex justify-end"><button type="button" class="mr-3 rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700" @click="syncVisualContent">Sincronizeaza editorul</button><button :disabled="form.processing" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Salveaza</button></div>
        </form></div></div>
    </AuthenticatedLayout>
</template>
