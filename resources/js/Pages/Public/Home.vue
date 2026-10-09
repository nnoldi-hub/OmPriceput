<script setup>
import Icon from '@/Components/Icon.vue';
import PackageCard from '@/Components/PackageCard.vue';
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    packages: Array,
    latestPosts: Array,
    stats: Array,
    page: Object,
});
const heroTitle = computed(() => props.page?.content?.hero_title || 'Reparam. Montam. Lasam totul ca atunci.');
const heroIntro = computed(() => props.page?.content?.intro || 'Lucrari de electrician, instalator, zugrav si montaj pentru casa si apartament. Trimiteti cererea, venim la constatare si primiti devizul scris inainte de a incepe lucrarea.');
const sections = computed(() => props.page?.sections || []);
const hasSection = (type) => sections.value.some((section) => section.content?.type === type || section.section_key === type);
const sectionContent = (section) => section.content || {};

function formatDate(value) {
    return new Date(value).toLocaleDateString('ro-RO', { day: 'numeric', month: 'long', year: 'numeric' });
}

const fallbackStats = [
    { icon: 'clock', value: '24h', label: 'Raspuns la cerere' },
    { icon: 'home', value: '1 zi', label: 'Programarea constatarii' },
    { icon: 'shield', value: 'Scris', label: 'Deviz inainte de lucrare' },
    { icon: 'bolt', value: 'Fara surprize', label: 'Pret final la locul lucrarii' },
];

const steps = [
    { icon: 'clipboard', title: 'Cerere si fotografii', text: 'Ne spuneti ce aveti nevoie si atasati cateva fotografii ale problemei.' },
    { icon: 'truck', title: 'Constatare', text: 'Venim la locul lucrarii pentru a identifica problema si a stabili materialele necesare.' },
    { icon: 'wrench', title: 'Deviz scris', text: 'Primiti devizul cu manopera si materialele prezentate separat.' },
    { icon: 'lifebuoy', title: 'Executia lucrarii', text: 'Dupa acceptarea devizului, programam interventia si remediem problema.' },
];
</script>

<template>
    <SeoHead />

    <PublicLayout>
        <section class="relative overflow-hidden bg-[#021a2d]">
            <div class="pointer-events-none absolute -left-32 -top-32 h-96 w-96 rounded-full bg-orange-500/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-24 top-1/3 h-80 w-80 rounded-full bg-blue-500/20 blur-3xl"></div>
            <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,rgba(255,255,255,0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.04)_1px,transparent_1px)] bg-[size:44px_44px] [mask-image:radial-gradient(ellipse_80%_60%_at_50%_0%,black,transparent)]"></div>

            <div class="relative mx-auto grid max-w-7xl grid-cols-1 items-center gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[1.2fr_0.8fr] lg:px-8 lg:py-24">
                <div class="max-w-xl">
                    <span class="inline-flex items-center gap-2 rounded-full border border-orange-400/40 bg-orange-500/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-orange-300">
                        <Icon name="sparkles" class="h-3.5 w-3.5" />
                        Lucrari pentru casa si apartament
                    </span>
                    <div class="mt-4 flex items-center gap-2 text-white/90">
                        <img src="/branding/logo-trim.webp" alt="Om Priceput" width="480" height="480" class="h-12 w-auto max-w-[260px] object-contain sm:h-14 lg:h-16" />
                    </div>
                    <h1 class="mt-6 font-display text-5xl font-extrabold tracking-[-0.05em] text-white sm:text-7xl">
                        Reparam. Montam.
                        <span class="block text-orange-500">Lasam totul ca atunci.</span>
                    </h1>
                    <p class="mt-6 max-w-[620px] text-lg leading-8 text-slate-300">
                        Lucrari de electrician, instalator, zugrav si montaj pentru casa si apartament. Trimiteti cererea, venim la constatare si primiti devizul scris inainte de a incepe lucrarea.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <Link
                            :href="route('public.quote')"
                            class="rounded-md bg-orange-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-orange-500/25 transition hover:bg-orange-400"
                        >
                            Cereti un deviz gratuit
                        </Link>
                        <Link
                            :href="route('public.services')"
                            class="rounded-md border border-slate-600 px-6 py-3 text-sm font-semibold text-white transition hover:border-slate-400 hover:bg-white/5"
                        >
                            Ce facem
                        </Link>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 lg:max-w-xl lg:justify-self-end">
                    <div
                        v-for="stat in (stats?.length ? stats.map((item) => ({ icon: item.icon || 'shield', value: item.value, label: item.description })) : fallbackStats)"
                        :key="stat.label"
                        class="group rounded-2xl border border-white/10 bg-white/5 p-6 shadow-[0_0_0_1px_rgba(255,255,255,0.03)] backdrop-blur transition hover:border-orange-400/40 hover:bg-white/10"
                    >
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-blue-600 text-white shadow-lg shadow-orange-500/20">
                            <Icon :name="stat.icon" class="h-5 w-5" />
                        </div>
                        <dd class="mt-4 text-3xl font-bold text-white">{{ stat.value }}</dd>
                        <dt class="mt-1 text-sm text-slate-300">{{ stat.label }}</dt>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="hasSection('stats')" class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div v-for="section in sections.filter((item) => (item.content?.type || item.section_key) === 'stats')" :key="section.id" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div v-for="item in sectionContent(section).items" :key="item.label" class="rounded-xl border border-slate-200 bg-white p-5 text-center shadow-sm"><div class="text-2xl font-bold text-blue-600">{{ item.value }}</div><div class="mt-1 text-sm text-slate-500">{{ item.label }}</div></div>
            </div>
        </section>
        <section v-if="hasSection('packages')" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div v-for="section in sections.filter((item) => (item.content?.type || item.section_key) === 'packages')" :key="section.id">
                <div class="text-center"><h2 class="font-display text-3xl font-bold text-slate-900">{{ sectionContent(section).title || 'Pachete de intretinere' }}</h2></div>
                <div class="mt-10 grid grid-cols-1 gap-8 lg:grid-cols-3"><PackageCard v-for="pkg in packages.filter((item) => !sectionContent(section).items?.length || sectionContent(section).items.includes(item.id))" :key="pkg.key" :pkg="pkg" /></div>
            </div>
        </section>
        <section v-if="hasSection('process') || hasSection('benefits')" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div v-for="section in sections.filter((item) => ['process', 'benefits'].includes(item.content?.type || item.section_key))" :key="section.id" class="mb-10 text-center">
                <h2 class="font-display text-3xl font-bold text-slate-900">{{ sectionContent(section).title || (section.section_key === 'process' ? 'Cum lucram' : 'Beneficii') }}</h2>
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><div v-for="item in sectionContent(section).items" :key="item.title || item" class="rounded-xl border border-slate-200 p-5 text-slate-600">{{ item.title || item }}</div></div>
            </div>
        </section>
        <section v-for="section in sections.filter((item) => ['cta', 'text_image', 'gallery', 'html'].includes(item.content?.type || item.section_key))" :key="section.id" class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div v-if="(section.content?.type || section.section_key) === 'cta'" class="rounded-2xl bg-brand-navy p-10 text-center text-white"><h2 class="text-3xl font-bold">{{ sectionContent(section).title }}</h2><p class="mx-auto mt-3 max-w-2xl text-slate-300">{{ sectionContent(section).text }}</p><Link :href="sectionContent(section).button_link || route('public.quote')" class="mt-6 inline-block rounded-md bg-orange-500 px-5 py-3 font-semibold">{{ sectionContent(section).button_text || 'Contactati-ne' }}</Link></div>
            <div v-else-if="(section.content?.type || section.section_key) === 'text_image'" class="grid items-center gap-8 md:grid-cols-2"><div><h2 class="text-3xl font-bold text-slate-900">{{ sectionContent(section).title }}</h2><p class="mt-4 whitespace-pre-line text-slate-600">{{ sectionContent(section).text }}</p></div><img v-if="sectionContent(section).image" :src="sectionContent(section).image" :alt="sectionContent(section).image_alt || sectionContent(section).title" class="w-full rounded-xl object-cover" loading="lazy" decoding="async" /></div>
            <div v-else-if="(section.content?.type || section.section_key) === 'gallery'" class="grid grid-cols-2 gap-4 md:grid-cols-4"><img v-for="image in sectionContent(section).images" :key="image" :src="image" alt="" class="h-40 w-full rounded-lg object-cover" loading="lazy" decoding="async" /></div>
            <div v-else class="prose max-w-none" v-html="sectionContent(section).html"></div>
        </section>
        <section v-if="!hasSection('packages')" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="font-display text-3xl font-bold text-slate-900">Pachete de intretinere</h2>
                <p class="mt-3 text-slate-500">Alegeti un pachet cu un numar fix de vizite sau solicitati un deviz dupa constatare.</p>
            </div>
            <div class="mt-10 grid grid-cols-1 gap-8 lg:grid-cols-3">
                <PackageCard v-for="pkg in packages" :key="pkg.key" :pkg="pkg" />
            </div>
        </section>

        <section class="relative overflow-hidden bg-slate-50 py-16">
            <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,rgba(15,23,42,0.05)_1px,transparent_1px),linear-gradient(to_bottom,rgba(15,23,42,0.05)_1px,transparent_1px)] bg-[size:40px_40px]"></div>
            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="font-display text-3xl font-bold text-slate-900">Cum lucram</h2>
                </div>
                <div class="relative mt-12">
                    <div class="absolute left-0 right-0 top-6 hidden h-0.5 bg-gradient-to-r from-orange-300 via-slate-300 to-blue-300 sm:block"></div>
                    <div class="grid grid-cols-1 gap-10 sm:grid-cols-4">
                        <div v-for="step in steps" :key="step.title" class="relative text-center">
                            <div class="relative mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white text-blue-600 shadow-md ring-4 ring-slate-50">
                                <Icon :name="step.icon" class="h-5 w-5" />
                            </div>
                            <h3 class="mt-4 font-display font-semibold text-slate-900">{{ step.title }}</h3>
                            <p class="mt-2 text-sm text-slate-500">{{ step.text }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="latestPosts.length" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-3xl font-bold text-slate-900">Din blog</h2>
                <Link :href="route('public.blog.index')" class="text-sm font-semibold text-blue-600 hover:text-blue-500">
                    Vezi toate articolele &rarr;
                </Link>
            </div>
            <div class="mt-8 grid grid-cols-1 gap-8 sm:grid-cols-3">
                <Link
                    v-for="post in latestPosts"
                    :key="post.slug"
                    :href="route('public.blog.show', post.slug)"
                    class="group block overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl"
                >
                    <div class="flex h-28 items-center justify-center bg-gradient-to-br from-brand-navy via-blue-900 to-brand-navy">
                        <Icon name="wrench" class="h-9 w-9 text-orange-400/80" />
                    </div>
                    <div class="p-6">
                        <time class="text-xs font-medium uppercase tracking-wide text-orange-600">{{ formatDate(post.published_at) }}</time>
                        <h3 class="mt-2 font-display font-semibold text-slate-900 group-hover:text-blue-600">{{ post.title }}</h3>
                        <p class="mt-2 text-sm text-slate-500">{{ post.excerpt }}</p>
                    </div>
                </Link>
            </div>
        </section>
    </PublicLayout>
</template>
