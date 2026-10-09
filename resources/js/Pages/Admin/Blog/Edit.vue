<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MediaPicker from '@/Components/MediaPicker.vue';
import TinyEditor from '@/Components/TinyEditor.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    post: Object,
    categories: Array,
    tags: Array,
    authors: Array,
    galleryMedia: { type: Array, default: () => [] },
});

function toLocalInput(value) {
    if (!value) return '';
    const date = new Date(value);
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function slugify(text) {
    return (text || '')
        .toString()
        .replace(/[ăâ]/gi, 'a')
        .replace(/î/gi, 'i')
        .replace(/[șş]/gi, 's')
        .replace(/[țţ]/gi, 't')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
}

const form = useForm({
    title: props.post?.title || '',
    slug: props.post?.slug || '',
    excerpt: props.post?.excerpt || '',
    body: props.post?.body || '',
    cover_image: null,
    cover_image_alt: props.post?.cover_image_alt || '',
    cover_image_caption: props.post?.cover_image_caption || '',
    cover_image_credit: props.post?.cover_image_credit || '',
    author_id: props.post?.author_id || '',
    meta_title: props.post?.meta_title || '',
    meta_description: props.post?.meta_description || '',
    focus_keyword: props.post?.focus_keyword || '',
    canonical_url: props.post?.canonical_url || '',
    meta_robots: props.post?.meta_robots || '',
    og_title: props.post?.og_title || '',
    og_description: props.post?.og_description || '',
    og_image: null,
    twitter_title: props.post?.twitter_title || '',
    twitter_description: props.post?.twitter_description || '',
    twitter_image: null,
    status: props.post?.status || 'draft',
    published_at: props.post?.published_at ? toLocalInput(props.post.published_at) : '',
    categories: (props.post?.categories || []).map((category) => category.id),
    tags: (props.post?.tags || []).map((tag) => tag.id),
    gallery: (props.post?.gallery || []).map(Number),
    faq: (props.post?.faq || []).map((item) => ({ question: item.question || '', answer: item.answer || '' })),
    auto_internal_links: Boolean(props.post?.auto_internal_links),
});

const editorRef = ref(null);
const showMediaPicker = ref(false);
const mediaTarget = ref('editor');
const galleryItems = ref([...(props.galleryMedia || [])]);

function syncGallery() {
    form.gallery = galleryItems.value.map((item) => item.id);
}

function openEditorMedia() {
    mediaTarget.value = 'editor';
    showMediaPicker.value = true;
}

function openGalleryPicker() {
    mediaTarget.value = 'gallery';
    showMediaPicker.value = true;
}

function pickerSelectedIds() {
    return mediaTarget.value === 'gallery' ? form.gallery : [];
}

function onMediaSelect(items) {
    if (mediaTarget.value === 'editor') {
        const item = items[0];
        if (item) editorRef.value?.insertImage(item.url, item.alt);
        return;
    }

    items.forEach((item) => {
        if (!galleryItems.value.some((existing) => existing.id === item.id)) {
            galleryItems.value.push(item);
        }
    });
    syncGallery();
}

function removeGalleryItem(id) {
    galleryItems.value = galleryItems.value.filter((item) => item.id !== id);
    syncGallery();
}

function moveGalleryItem(index, direction) {
    const target = index + direction;
    if (target < 0 || target >= galleryItems.value.length) return;
    const list = [...galleryItems.value];
    [list[index], list[target]] = [list[target], list[index]];
    galleryItems.value = list;
    syncGallery();
}

const slugTouched = ref(Boolean(props.post?.slug));
const coverPreview = ref(props.post?.cover_image_url || null);

function onTitleInput() {
    if (!slugTouched.value) {
        form.slug = slugify(form.title);
    }
}

function onCoverChange(event) {
    const file = event.target.files?.[0] ?? null;
    form.cover_image = file;
    if (file) {
        coverPreview.value = URL.createObjectURL(file);
    }
}

function toggleTag(id) {
    const index = form.tags.indexOf(id);
    if (index === -1) form.tags.push(id);
    else form.tags.splice(index, 1);
}

function addFaq() {
    form.faq.push({ question: '', answer: '' });
}

function removeFaq(index) {
    form.faq.splice(index, 1);
}

const titleLength = computed(() => (form.meta_title || form.title || '').length);
const descriptionLength = computed(() => (form.meta_description || form.excerpt || '').length);

const seoChecks = computed(() => {
    const keyword = (form.focus_keyword || '').toLowerCase().trim();
    const title = (form.meta_title || form.title || '').toLowerCase();
    const description = (form.meta_description || form.excerpt || '').toLowerCase();

    return [
        { label: 'Titlu SEO între 30 și 60 de caractere', ok: title.length >= 30 && title.length <= 60 },
        { label: 'Meta description între 120 și 160 de caractere', ok: description.length >= 120 && description.length <= 160 },
        { label: 'Focus keyword definit', ok: keyword.length > 0 },
        { label: 'Focus keyword în titlu', ok: keyword.length > 0 && title.includes(keyword) },
        { label: 'Focus keyword în descriere', ok: keyword.length > 0 && description.includes(keyword) },
        { label: 'Slug definit', ok: (form.slug || '').length > 0 },
        { label: 'Imagine de copertă', ok: Boolean(props.post?.cover_image) || Boolean(form.cover_image) },
        { label: 'Categorie selectată', ok: form.categories.length > 0 },
    ];
});

const seoScore = computed(() => {
    const checks = allChecks.value;
    return Math.round((checks.filter((check) => check.ok).length / checks.length) * 100);
});

const bodyStats = computed(() => {
    const html = form.body || '';
    let text = '';
    let internalLinks = 0;
    let externalLinks = 0;
    let images = 0;
    let imagesWithAlt = 0;
    let headingText = '';

    if (html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        text = doc.body.textContent || '';

        const ownHost = window.location.host;
        doc.querySelectorAll('a[href]').forEach((anchor) => {
            const href = anchor.getAttribute('href') || '';
            if (href.startsWith('/') || href.startsWith('#') || href.includes(ownHost)) internalLinks += 1;
            else if (href.startsWith('http')) externalLinks += 1;
        });

        const imageNodes = doc.querySelectorAll('img');
        images = imageNodes.length;
        imageNodes.forEach((image) => {
            if ((image.getAttribute('alt') || '').trim()) imagesWithAlt += 1;
        });

        headingText = Array.from(doc.querySelectorAll('h2, h3')).map((heading) => heading.textContent).join(' ');
    }

    const words = text.trim().split(/\s+/).filter(Boolean);
    const wordCount = words.length;
    const keyword = (form.focus_keyword || '').trim().toLowerCase();
    const keywordCount = keyword ? text.toLowerCase().split(keyword).length - 1 : 0;
    const density = wordCount ? (keywordCount / wordCount) * 100 : 0;
    const intro = words.slice(0, 50).join(' ').toLowerCase();

    return {
        wordCount,
        keywordCount,
        density,
        internalLinks,
        externalLinks,
        images,
        imagesWithAlt,
        keywordInIntro: Boolean(keyword) && intro.includes(keyword),
        keywordInHeading: Boolean(keyword) && headingText.toLowerCase().includes(keyword),
    };
});

const advancedChecks = computed(() => {
    const stats = bodyStats.value;
    const hasKeyword = Boolean((form.focus_keyword || '').trim());

    return [
        { label: `Conținut suficient (${stats.wordCount} cuvinte, minim 300)`, ok: stats.wordCount >= 300 },
        { label: hasKeyword ? `Densitate keyword ${stats.density.toFixed(2)}% (ideal 0.5–2.5%)` : 'Densitate keyword (setează focus keyword)', ok: hasKeyword && stats.density >= 0.5 && stats.density <= 2.5 },
        { label: 'Keyword în introducere (primele 50 de cuvinte)', ok: stats.keywordInIntro },
        { label: 'Keyword într-un subtitlu (H2/H3)', ok: stats.keywordInHeading },
        { label: `Linkuri interne (${stats.internalLinks}, recomandat minim 2)`, ok: stats.internalLinks >= 2 },
        { label: `Linkuri externe (${stats.externalLinks}, recomandat minim 1)`, ok: stats.externalLinks >= 1 },
        { label: stats.images ? `Imagini cu text alt (${stats.imagesWithAlt}/${stats.images})` : 'Imagini în conținut', ok: stats.images >= 1 && stats.imagesWithAlt === stats.images },
    ];
});

const allChecks = computed(() => [...seoChecks.value, ...advancedChecks.value]);

const previewTitle = computed(() => form.meta_title || form.title || 'Titlul articolului');
const previewDescription = computed(() => form.meta_description || form.excerpt || 'Descrierea articolului va apărea aici în rezultatele căutării.');
const previewUrl = computed(() => form.canonical_url || `https://ompriceput.ro/blog/${form.slug || 'slug-articol'}`);

function submit() {
    try {
        editorRef.value?.sync?.();
    } catch (e) {
        // continutul este oricum sincronizat prin v-model
    }

    const options = { forceFormData: true, preserveScroll: true };

    if (props.post) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.blog.update', props.post.id), options);
    } else {
        form.post(route('admin.blog.store'), options);
    }
}
</script>

<template>
    <Head :title="post ? 'Editează articol' : 'Articol nou'" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">{{ post ? 'Editează articol' : 'Articol blog nou' }}</h2>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="post?.status === 'published'"
                        :href="route('public.blog.show', post.slug)"
                        target="_blank"
                        class="text-sm font-semibold text-blue-600 hover:text-blue-500"
                    >
                        Vezi articolul
                    </Link>
                    <Link :href="route('admin.blog.index')" class="text-sm text-slate-500 hover:text-slate-700">Înapoi la listă</Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <form class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-4 lg:px-8" @submit.prevent="submit">
                <!-- Coloana principală -->
                <div class="space-y-6 lg:col-span-3">
                    <div class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                        <label class="block text-sm font-medium text-slate-700">
                            Titlu
                            <input v-model="form.title" class="mt-1 block w-full rounded-md border-slate-300 text-lg font-semibold" placeholder="Cum alegi un flex profesional" @input="onTitleInput" />
                            <span v-if="form.errors.title" class="mt-1 block text-xs text-red-600">{{ form.errors.title }}</span>
                        </label>

                        <label class="block text-sm font-medium text-slate-700">
                            Slug (URL)
                            <div class="mt-1 flex items-center rounded-md border border-slate-300 focus-within:ring-1 focus-within:ring-blue-500">
                                <span class="hidden select-none border-r border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-400 sm:inline">/blog/</span>
                                <input v-model="form.slug" class="w-full rounded-md border-0 text-sm focus:ring-0" placeholder="generat-automat" @input="slugTouched = true" />
                            </div>
                            <span v-if="form.errors.slug" class="mt-1 block text-xs text-red-600">{{ form.errors.slug }}</span>
                        </label>

                        <label class="block text-sm font-medium text-slate-700">
                            Rezumat
                            <textarea v-model="form.excerpt" rows="2" class="mt-1 block w-full rounded-md border-slate-300" placeholder="Scurtă introducere folosită în listări și ca descriere implicită" />
                            <span v-if="form.errors.excerpt" class="mt-1 block text-xs text-red-600">{{ form.errors.excerpt }}</span>
                        </label>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-700">Conținut articol</span>
                            <span class="text-xs text-slate-400">Editor vizual (TinyMCE)</span>
                        </div>
                        <TinyEditor
                            ref="editorRef"
                            v-model="form.body"
                            :height="640"
                            :upload-url="route('admin.media.store')"
                            @media="openEditorMedia"
                        />
                        <span v-if="form.errors.body" class="mt-1 block text-xs text-red-600">{{ form.errors.body }}</span>
                    </div>

                    <!-- Galerie imagini -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-base font-semibold text-slate-900">Galerie imagini</h3>
                            <button type="button" class="rounded-md bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200" @click="openGalleryPicker">
                                + Adaugă din bibliotecă
                            </button>
                        </div>
                        <p v-if="!galleryItems.length" class="text-sm text-slate-400">Nicio imagine in galerie. Adaugă din biblioteca media.</p>
                        <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            <div v-for="(item, index) in galleryItems" :key="item.id" class="group relative overflow-hidden rounded-md border border-slate-200">
                                <img :src="item.url" :alt="item.alt || ''" class="h-28 w-full object-cover" />
                                <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-slate-900/60 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100">
                                    <button type="button" :disabled="index === 0" class="disabled:opacity-30" @click="moveGalleryItem(index, -1)">&larr;</button>
                                    <button type="button" class="font-medium text-red-300" @click="removeGalleryItem(item.id)">Șterge</button>
                                    <button type="button" :disabled="index === galleryItems.length - 1" class="disabled:opacity-30" @click="moveGalleryItem(index, 1)">&rarr;</button>
                                </div>
                            </div>
                        </div>
                        <span v-if="form.errors.gallery" class="mt-1 block text-xs text-red-600">{{ form.errors.gallery }}</span>
                    </div>

                    <!-- FAQ -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">Întrebări frecvente (FAQ)</h3>
                                <p class="text-xs text-slate-400">Se afișează pe pagină și generează schema FAQPage pentru Google.</p>
                            </div>
                            <button type="button" class="rounded-md bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200" @click="addFaq">
                                + Adaugă întrebare
                            </button>
                        </div>
                        <p v-if="!form.faq.length" class="text-sm text-slate-400">Nicio întrebare adăugată.</p>
                        <div v-else class="space-y-3">
                            <div v-for="(item, index) in form.faq" :key="index" class="rounded-md border border-slate-200 p-3">
                                <div class="flex items-start gap-3">
                                    <div class="flex-1 space-y-2">
                                        <input v-model="item.question" placeholder="Întrebare" class="block w-full rounded-md border-slate-300 text-sm font-medium" />
                                        <textarea v-model="item.answer" rows="2" placeholder="Răspuns" class="block w-full rounded-md border-slate-300 text-sm" />
                                    </div>
                                    <button type="button" class="text-sm text-red-600 hover:text-red-500" @click="removeFaq(index)">Șterge</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEO -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-900">SEO</h3>
                        <div class="mt-4 space-y-4">
                            <label class="block text-sm font-medium text-slate-700">
                                SEO title
                                <input v-model="form.meta_title" class="mt-1 block w-full rounded-md border-slate-300" />
                                <span class="mt-1 block text-xs" :class="titleLength >= 30 && titleLength <= 60 ? 'text-green-600' : 'text-amber-600'">{{ titleLength }} caractere (recomandat 30-60)</span>
                            </label>

                            <label class="block text-sm font-medium text-slate-700">
                                Meta description
                                <textarea v-model="form.meta_description" rows="3" class="mt-1 block w-full rounded-md border-slate-300" />
                                <span class="mt-1 block text-xs" :class="descriptionLength >= 120 && descriptionLength <= 160 ? 'text-green-600' : 'text-amber-600'">{{ descriptionLength }} caractere (recomandat 120-160)</span>
                            </label>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="block text-sm font-medium text-slate-700">
                                    Focus keyword
                                    <input v-model="form.focus_keyword" class="mt-1 block w-full rounded-md border-slate-300" placeholder="flex profesional" />
                                </label>
                                <label class="block text-sm font-medium text-slate-700">
                                    Robots
                                    <select v-model="form.meta_robots" class="mt-1 block w-full rounded-md border-slate-300">
                                        <option value="">Implicit (index, follow)</option>
                                        <option value="index,follow">index, follow</option>
                                        <option value="index,nofollow">index, nofollow</option>
                                        <option value="noindex,follow">noindex, follow</option>
                                        <option value="noindex,nofollow">noindex, nofollow</option>
                                    </select>
                                </label>
                            </div>

                            <label class="block text-sm font-medium text-slate-700">
                                Canonical URL
                                <input v-model="form.canonical_url" class="mt-1 block w-full rounded-md border-slate-300" placeholder="https://ompriceput.ro/blog/slug" />
                                <span v-if="form.errors.canonical_url" class="mt-1 block text-xs text-red-600">{{ form.errors.canonical_url }}</span>
                            </label>

                            <label class="flex items-start gap-2 rounded-md border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                                <input v-model="form.auto_internal_links" type="checkbox" class="mt-0.5 rounded border-slate-300" />
                                <span>
                                    <span class="font-medium">Linkare internă automată la salvare</span>
                                    <span class="mt-0.5 block text-xs text-slate-400">Transformă automat în link prima apariție a titlurilor altor articole publicate (max 5).</span>
                                </span>
                            </label>

                            <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Previzualizare Google</div>
                                <div class="mt-2 rounded-md bg-white p-4">
                                    <div class="text-xs text-green-700">{{ previewUrl }}</div>
                                    <div class="mt-0.5 text-lg text-blue-700">{{ previewTitle }}</div>
                                    <div class="mt-0.5 text-sm text-slate-600">{{ previewDescription }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Open Graph / Twitter -->
                    <details class="rounded-lg bg-white p-6 shadow-sm">
                        <summary class="cursor-pointer text-base font-semibold text-slate-900">Social media (Open Graph &amp; Twitter)</summary>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="space-y-4">
                                <h4 class="text-sm font-semibold text-slate-700">Facebook / Open Graph</h4>
                                <label class="block text-sm font-medium text-slate-700">OG title<input v-model="form.og_title" class="mt-1 block w-full rounded-md border-slate-300" /></label>
                                <label class="block text-sm font-medium text-slate-700">OG description<textarea v-model="form.og_description" rows="2" class="mt-1 block w-full rounded-md border-slate-300" /></label>
                                <label class="block text-sm font-medium text-slate-700">OG image<input type="file" accept="image/*" class="mt-1 block w-full text-sm" @input="form.og_image = $event.target.files[0] ?? null" /></label>
                            </div>
                            <div class="space-y-4">
                                <h4 class="text-sm font-semibold text-slate-700">Twitter / X</h4>
                                <label class="block text-sm font-medium text-slate-700">Twitter title<input v-model="form.twitter_title" class="mt-1 block w-full rounded-md border-slate-300" /></label>
                                <label class="block text-sm font-medium text-slate-700">Twitter description<textarea v-model="form.twitter_description" rows="2" class="mt-1 block w-full rounded-md border-slate-300" /></label>
                                <label class="block text-sm font-medium text-slate-700">Twitter image<input type="file" accept="image/*" class="mt-1 block w-full text-sm" @input="form.twitter_image = $event.target.files[0] ?? null" /></label>
                            </div>
                        </div>
                    </details>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6 lg:col-span-1">
                    <!-- Publicare -->
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">Publicare</h3>
                        <div class="mt-3 space-y-3">
                            <button type="submit" :disabled="form.processing" class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
                                {{ form.processing ? 'Se salvează...' : 'Salvează articolul' }}
                            </button>
                            <label class="block text-sm font-medium text-slate-700">Status
                                <select v-model="form.status" class="mt-1 block w-full rounded-md border-slate-300">
                                    <option value="draft">Ciornă</option>
                                    <option value="published">Publicat</option>
                                </select>
                            </label>
                            <label class="block text-sm font-medium text-slate-700">Programare publicare
                                <input v-model="form.published_at" type="datetime-local" class="mt-1 block w-full rounded-md border-slate-300" />
                                <span class="mt-1 block text-xs text-slate-400">Lasă gol pentru publicare imediată.</span>
                            </label>
                            <label class="block text-sm font-medium text-slate-700">Autor
                                <select v-model="form.author_id" class="mt-1 block w-full rounded-md border-slate-300">
                                    <option value="">— implicit —</option>
                                    <option v-for="author in authors" :key="author.id" :value="author.id">{{ author.name }}</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <!-- Imagine copertă -->
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">Imagine de copertă</h3>
                        <div class="mt-3">
                            <img v-if="coverPreview" :src="coverPreview" alt="" class="mb-3 h-36 w-full rounded-md object-cover" />
                            <input type="file" accept="image/*" class="block w-full text-sm" @input="onCoverChange" />
                            <div class="mt-3 space-y-3">
                                <input v-model="form.cover_image_alt" placeholder="Text alternativ (alt)" class="block w-full rounded-md border-slate-300 text-sm" />
                                <input v-model="form.cover_image_caption" placeholder="Legendă imagine" class="block w-full rounded-md border-slate-300 text-sm" />
                                <input v-model="form.cover_image_credit" placeholder="Credit foto" class="block w-full rounded-md border-slate-300 text-sm" />
                            </div>
                        </div>
                    </div>

                    <!-- SEO score -->
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-900">Scor SEO</h3>
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-bold"
                                :class="seoScore >= 80 ? 'bg-green-100 text-green-700' : seoScore >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'"
                            >{{ seoScore }}/100</span>
                        </div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full transition-all" :class="seoScore >= 80 ? 'bg-green-500' : seoScore >= 50 ? 'bg-amber-500' : 'bg-red-500'" :style="{ width: seoScore + '%' }" />
                        </div>
                        <ul class="mt-3 space-y-1.5 text-xs">
                            <li v-for="check in allChecks" :key="check.label" class="flex items-start gap-2" :class="check.ok ? 'text-green-700' : 'text-slate-500'">
                                <span>{{ check.ok ? '✅' : '⚠️' }}</span>
                                <span>{{ check.label }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Categorii -->
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">Categorii</h3>
                        <p v-if="!categories.length" class="mt-2 text-xs text-slate-400">Nicio categorie. <Link :href="route('admin.blog.categories.index')" class="text-blue-600">Adaugă una</Link>.</p>
                        <div v-else class="mt-3 max-h-52 space-y-1.5 overflow-y-auto pr-1">
                            <label v-for="category in categories" :key="category.id" class="flex items-center gap-2 text-sm" :class="category.parent_id ? 'ml-4' : 'font-medium'">
                                <input v-model="form.categories" type="checkbox" :value="category.id" class="rounded border-slate-300" />
                                <span>{{ category.name }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Tag-uri -->
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">Tag-uri</h3>
                        <p v-if="!tags.length" class="mt-2 text-xs text-slate-400">Niciun tag. <Link :href="route('admin.blog.tags.index')" class="text-blue-600">Adaugă unul</Link>.</p>
                        <div v-else class="mt-3 flex flex-wrap gap-2">
                            <button
                                v-for="tag in tags"
                                :key="tag.id"
                                type="button"
                                class="rounded-full border px-3 py-1 text-xs font-medium transition"
                                :class="form.tags.includes(tag.id) ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 text-slate-600 hover:border-blue-400'"
                                @click="toggleTag(tag.id)"
                            >
                                {{ tag.name }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <MediaPicker
                :show="showMediaPicker"
                :multiple="mediaTarget === 'gallery'"
                :selected="pickerSelectedIds()"
                @close="showMediaPicker = false"
                @select="onMediaSelect"
            />
        </div>
    </AuthenticatedLayout>
</template>
