<script setup>
import Icon from '@/Components/Icon.vue';
import PackageCard from '@/Components/PackageCard.vue';
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    packages: Array,
    latestPosts: Array,
    stats: Array,
    page: Object,
    reviews: { type: Array, default: () => [] },
    reviewStats: { type: Object, default: () => ({ count: 0, average: 0 }) },
});

const page = usePage();
const phone = computed(() => page.props.siteSettings?.company_phone || '');
const phoneHref = computed(() => `tel:${String(phone.value).replace(/[^\d+]/g, '')}`);
const heroTitle = computed(() => props.page?.content?.hero_title || 'Reparam. Montam. Fara batai de cap.');
const heroIntro = computed(() => props.page?.content?.intro || 'Un singur meserias de incredere pentru toate lucrarile din casa ta.\nPrimesti constatare, deviz scris si programare clara inainte de inceperea lucrarii.');

const heroParts = computed(() => {
    const title = heroTitle.value;
    const split = title.lastIndexOf('. ');

    return split === -1 ? [title, ''] : [title.slice(0, split + 1), title.slice(split + 2)];
});

const heroIntroParts = computed(() => {
    const lines = heroIntro.value.split('\n').map((line) => line.trim()).filter(Boolean);

    return [lines[0] || heroIntro.value, lines.slice(1).join(' ')];
});

const heroGuarantees = [
    'Constatare si discutie inainte de inceperea lucrarii',
    'Deviz scris, cu materiale si manopera separate',
    'Fara obligatie de acceptare',
];

const sections = computed(() => props.page?.sections || []);
const hasSection = (type) => sections.value.some((section) => section.content?.type === type || section.section_key === type);
const sectionContent = (section) => section.content || {};

function formatDate(value) {
    return new Date(value).toLocaleDateString('ro-RO', { day: 'numeric', month: 'long', year: 'numeric' });
}

function stars(rating) {
    const value = Number(rating) || 0;
    return '★'.repeat(value) + '☆'.repeat(Math.max(0, 5 - value));
}

const fallbackStats = [
    { icon: 'clock', value: '24h', label: 'Raspuns la cerere' },
    { icon: 'home', value: '1 zi', label: 'Programarea constatarii' },
    { icon: 'shield', value: 'Scris', label: 'Deviz inainte de lucrare' },
    { icon: 'bolt', value: 'Fara surprize', label: 'Pret final la locul lucrarii' },
];

const reasons = [
    { icon: 'user', title: 'Un singur contact', text: 'Nu cauti separat electrician, instalator si montator. Te ocupi de o singura persoana pentru toate lucrarile.' },
    { icon: 'file-text', title: 'Deviz scris inainte de lucrare', text: 'Stii costul materialelor si al manoperei inainte sa incepem.' },
    { icon: 'shield-check', title: 'Fara costuri ascunse', text: 'Pretul final se stabileste la locul lucrarii, dupa constatare.' },
    { icon: 'message-square', title: 'Comunicare clara', text: 'Confirmam ora si te anuntam la fiecare pas al lucrarii.' },
    { icon: 'sparkles', title: 'Lucram curat', text: 'Predam locul de lucru curat la final.' },
    { icon: 'wrench', title: 'Executie personala sau cu meseriasi de incredere', text: 'Lucrez personal sau cu oameni cu care am mai lucrat, nu cu necunoscuti.' },
];

const steps = [
    { icon: 'clipboard', title: 'Trimiti solicitarea', text: 'Descrii problema sau serviciul dorit, direct din formular.' },
    { icon: 'file-text', title: 'Primesti estimarea', text: 'Analizam cererea si iti comunicam costurile estimative.' },
    { icon: 'calendar', title: 'Stabilim interventia', text: 'Confirmam programarea si detaliile lucrarii.' },
    { icon: 'wrench', title: 'Executam lucrarea', text: 'Respectam solutia agreata si termenul stabilit.' },
    { icon: 'shield-check', title: 'Confirmare finala', text: 'Verificam impreuna rezultatul lucrarii.' },
];

const trustTrades = [
    { icon: 'bolt', title: 'Electricitate' },
    { icon: 'lifebuoy', title: 'Instalatii sanitare' },
    { icon: 'wrench', title: 'Montaj mobilier' },
    { icon: 'sparkles', title: 'Zugraveli si reparatii' },
    { icon: 'home', title: 'Intretinere locuinta' },
];

const popularServices = [
    { icon: 'wrench', title: 'Montaj mobilier', text: 'Montam mobilier IKEA, JYSK, Dedeman si mobilier personalizat.' },
    { icon: 'lifebuoy', title: 'Inlocuire baterii si obiecte sanitare', text: 'Reparatii rapide si montaj profesional.' },
    { icon: 'bolt', title: 'Prize si corpuri de iluminat', text: 'Montaj si inlocuire componente electrice.' },
    { icon: 'sparkles', title: 'Zugraveli si reimprospatari', text: 'Lucrari pentru apartamente si case.' },
];

const serviceAreas = ['Bucuresti', 'Cernica', 'Pantelimon', 'Voluntari', 'Popesti-Leordeni', 'Bragadiru', 'Chiajna', 'Otopeni'];

const galleryImages = computed(() => {
    const gallery = sections.value.find((item) => (item.content?.type || item.section_key) === 'gallery');

    return gallery ? (sectionContent(gallery).images || []) : [];
});

const founderPhotoOk = ref(true);
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
                        {{ heroParts[0] }}
                        <span v-if="heroParts[1]" class="block text-orange-500">{{ heroParts[1] }}</span>
                    </h1>
                    <p class="mt-6 max-w-[620px] text-lg font-semibold leading-8 text-white">
                        {{ heroIntroParts[0] }}
                    </p>
                    <p v-if="heroIntroParts[1]" class="mt-2 max-w-[620px] text-base leading-7 text-slate-300">
                        {{ heroIntroParts[1] }}
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
                    <a v-if="phone" :href="phoneHref" class="mt-6 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <span class="inline-flex items-center gap-2 text-2xl font-extrabold text-white">
                            <Icon name="smartphone" class="h-6 w-6 text-orange-400" />
                            {{ phone }}
                        </span>
                        <span class="text-sm text-slate-300">Suna acum pentru constatare</span>
                    </a>
                    <ul class="mt-6 space-y-2 text-sm text-slate-300">
                        <li v-for="guarantee in heroGuarantees" :key="guarantee" class="flex items-start gap-2">
                            <Icon name="check" class="mt-0.5 h-4 w-4 flex-none text-orange-400" />
                            <span>{{ guarantee }}</span>
                        </li>
                    </ul>
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

        <section class="border-y border-slate-200 bg-slate-50">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    <div v-for="trade in trustTrades" :key="trade.title" class="flex items-center gap-3">
                        <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-orange-500/10 text-orange-500">
                            <Icon :name="trade.icon" class="h-5 w-5" />
                        </span>
                        <span class="text-sm font-semibold text-slate-700">{{ trade.title }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="font-display text-3xl font-bold text-slate-900">De ce Om Priceput?</h2>
                <p class="mx-auto mt-3 max-w-2xl text-slate-500">Un singur om de incredere pentru lucrarile din casa ta, cu deviz scris si programare clara.</p>
            </div>
            <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="reason in reasons"
                    :key="reason.title"
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                >
                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-blue-600 text-white shadow-lg shadow-orange-500/20">
                        <Icon :name="reason.icon" class="h-5 w-5" />
                    </div>
                    <h3 class="mt-4 font-display font-semibold text-slate-900">{{ reason.title }}</h3>
                    <p class="mt-2 text-sm text-slate-500">{{ reason.text }}</p>
                </div>
            </div>
        </section>

        <section v-if="hasSection('stats')" class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div v-for="section in sections.filter((item) => (item.content?.type || item.section_key) === 'stats')" :key="section.id" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div v-for="item in sectionContent(section).items" :key="item.label" class="rounded-xl border border-slate-200 bg-white p-5 text-center shadow-sm"><div class="text-2xl font-bold text-blue-600">{{ item.value }}</div><div class="mt-1 text-sm text-slate-500">{{ item.label }}</div></div>
            </div>
        </section>

        <section class="relative overflow-hidden bg-slate-50 py-16">
            <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,rgba(15,23,42,0.05)_1px,transparent_1px),linear-gradient(to_bottom,rgba(15,23,42,0.05)_1px,transparent_1px)] bg-[size:40px_40px]"></div>
            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="font-display text-3xl font-bold text-slate-900">Cum lucram</h2>
                </div>
                <div class="relative mt-12">
                    <div class="absolute left-0 right-0 top-6 hidden h-0.5 bg-gradient-to-r from-orange-300 via-slate-300 to-blue-300 lg:block"></div>
                    <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-5">
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

        <section v-if="hasSection('process') || hasSection('benefits')" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div v-for="section in sections.filter((item) => ['process', 'benefits'].includes(item.content?.type || item.section_key))" :key="section.id" class="mb-10 text-center">
                <h2 class="font-display text-3xl font-bold text-slate-900">{{ sectionContent(section).title || (section.section_key === 'process' ? 'Cum lucram' : 'Beneficii') }}</h2>
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><div v-for="item in sectionContent(section).items" :key="item.title || item" class="rounded-xl border border-slate-200 p-5 text-slate-600">{{ item.title || item }}</div></div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="font-display text-3xl font-bold text-slate-900">Servicii populare</h2>
                <p class="mx-auto mt-3 max-w-2xl text-slate-500">Cele mai cerute lucrari pentru apartamente si case, executate curat si la timp.</p>
            </div>
            <div class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div v-for="service in popularServices" :key="service.title" class="flex items-start gap-5 rounded-3xl border border-slate-200 bg-white p-8 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                    <div class="flex h-14 w-14 flex-none items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-blue-600 text-white shadow-lg shadow-orange-500/20">
                        <Icon :name="service.icon" class="h-7 w-7" />
                    </div>
                    <div>
                        <h3 class="font-display text-xl font-bold text-slate-900">{{ service.title }}</h3>
                        <p class="mt-2 text-slate-500">{{ service.text }}</p>
                    </div>
                </div>
            </div>
            <div class="mt-8 text-center">
                <Link :href="route('public.services')" class="text-sm font-semibold text-blue-600 hover:text-blue-500">
                    Vezi toate serviciile &rarr;
                </Link>
            </div>
        </section>

        <section class="bg-white py-16">
            <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
                <div class="justify-self-center">
                    <img
                        v-if="founderPhotoOk"
                        src="/branding/founder.jpg"
                        alt="Noldi Nyikora, fondator Om Priceput"
                        class="h-64 w-64 rounded-3xl object-cover shadow-xl sm:h-80 sm:w-80"
                        loading="lazy"
                        decoding="async"
                        @error="founderPhotoOk = false"
                    />
                    <div v-else class="flex h-64 w-64 items-center justify-center rounded-3xl bg-gradient-to-br from-brand-navy to-blue-900 text-6xl font-bold text-white shadow-xl sm:h-80 sm:w-80">
                        NN
                    </div>
                </div>
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-orange-400/40 bg-orange-500/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-orange-600">
                        <Icon name="user" class="h-3.5 w-3.5" />
                        Despre mine
                    </span>
                    <h2 class="mt-4 font-display text-3xl font-bold text-slate-900">Salut, sunt Noldi Nyikora.</h2>
                    <p class="mt-4 text-slate-600">
                        Am creat Om Priceput pentru persoanele care isi doresc un singur contact de incredere pentru
                        reparatiile si intretinerea locuintei. Fara alergat dupa mai multi meseriasi, fara surprize si
                        fara costuri ascunse.
                    </p>
                    <p class="mt-4 text-slate-600">
                        Fie ca ai nevoie de montaj mobilier, reparatii electrice sau interventii sanitare, scopul meu este
                        sa primesti o solutie clara, un pret comunicat inainte de lucrare si un rezultat realizat cu
                        responsabilitate.
                    </p>
                    <Link
                        :href="route('public.quote')"
                        class="mt-6 inline-flex rounded-md bg-orange-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-orange-500/25 transition hover:bg-orange-400"
                    >
                        Cere un deviz
                    </Link>
                </div>
            </div>
        </section>

        <section v-for="section in sections.filter((item) => ['cta', 'text_image', 'html'].includes(item.content?.type || item.section_key))" :key="section.id" class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div v-if="(section.content?.type || section.section_key) === 'cta'" class="rounded-2xl bg-brand-navy p-10 text-center text-white"><h2 class="text-3xl font-bold">{{ sectionContent(section).title }}</h2><p class="mx-auto mt-3 max-w-2xl text-slate-300">{{ sectionContent(section).text }}</p><Link :href="sectionContent(section).button_link || route('public.quote')" class="mt-6 inline-block rounded-md bg-orange-500 px-5 py-3 font-semibold">{{ sectionContent(section).button_text || 'Contactati-ne' }}</Link></div>
            <div v-else-if="(section.content?.type || section.section_key) === 'text_image'" class="grid items-center gap-8 md:grid-cols-2"><div><h2 class="text-3xl font-bold text-slate-900">{{ sectionContent(section).title }}</h2><p class="mt-4 whitespace-pre-line text-slate-600">{{ sectionContent(section).text }}</p></div><img v-if="sectionContent(section).image" :src="sectionContent(section).image" :alt="sectionContent(section).image_alt || sectionContent(section).title" class="w-full rounded-xl object-cover" loading="lazy" decoding="async" /></div>
            <div v-else class="prose max-w-none" v-html="sectionContent(section).html"></div>
        </section>

        <section v-if="hasSection('packages')" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div v-for="section in sections.filter((item) => (item.content?.type || item.section_key) === 'packages')" :key="section.id">
                <div class="text-center"><span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-orange-600">Pentru proprietarii care doresc intretinere periodica</span><h2 class="mt-2 font-display text-3xl font-bold text-slate-900">{{ sectionContent(section).title || 'Planuri de mentenanta pentru locuinta' }}</h2></div>
                <div class="mt-10 grid grid-cols-1 gap-8 lg:grid-cols-3"><PackageCard v-for="pkg in packages.filter((item) => !sectionContent(section).items?.length || sectionContent(section).items.includes(item.id))" :key="pkg.key" :pkg="pkg" /></div>
            </div>
        </section>
        <section v-else class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="text-center">
                <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-orange-600">Pentru proprietarii care doresc intretinere periodica</span>
                <h2 class="mt-2 font-display text-3xl font-bold text-slate-900">Planuri de mentenanta pentru locuinta</h2>
                <p class="mx-auto mt-3 max-w-2xl text-slate-500">Serviciu optional de intretinere recurenta. Cel mai des apelezi la noi pentru o lucrare punctuala, dar poti alege si un plan cu un numar fix de vizite.</p>
            </div>
            <div class="mt-10 grid grid-cols-1 gap-8 lg:grid-cols-3">
                <PackageCard v-for="pkg in packages" :key="pkg.key" :pkg="pkg" />
            </div>
        </section>

        <section class="bg-white py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="font-display text-3xl font-bold text-slate-900">Lucrari realizate</h2>
                    <p class="mx-auto mt-3 max-w-2xl text-slate-500">Fotografii reale din lucrari: montaj de prize, corpuri de iluminat, mobilier si mici reparatii.</p>
                </div>
                <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <template v-if="galleryImages.length">
                        <img v-for="image in galleryImages" :key="image" :src="image" alt="Lucrare realizata" class="h-48 w-full rounded-xl object-cover" loading="lazy" decoding="async" />
                    </template>
                    <template v-else>
                        <div v-for="index in 6" :key="index" class="flex h-48 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-slate-400">
                            <div class="text-center">
                                <Icon name="camera" class="mx-auto h-7 w-7" />
                                <p class="mt-2 text-xs font-medium">Foto in curand</p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <section v-if="reviews.length" class="bg-slate-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="font-display text-3xl font-bold text-slate-900">Ce spun clientii</h2>
                    <p v-if="reviewStats.count" class="mx-auto mt-3 text-slate-500">
                        <span class="text-amber-500">{{ stars(Math.round(reviewStats.average)) }}</span>
                        {{ reviewStats.average }} din 5 · {{ reviewStats.count }} recenzii
                    </p>
                </div>
                <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <figure
                        v-for="(review, index) in reviews"
                        :key="index"
                        class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                    >
                        <div class="text-lg text-amber-500">{{ stars(review.rating) }}</div>
                        <blockquote class="mt-3 flex-1 text-sm text-slate-600">{{ review.comment }}</blockquote>
                        <figcaption class="mt-4 text-sm font-semibold text-slate-900">
                            {{ review.name }}
                            <span v-if="review.service || review.city" class="font-normal text-slate-400">
                                · {{ [review.service, review.city].filter(Boolean).join(', ') }}
                            </span>
                        </figcaption>
                    </figure>
                </div>
                <div class="mt-10 text-center">
                    <Link :href="route('public.reviews.index')" class="text-sm font-semibold text-blue-600 hover:text-blue-500">
                        Vezi toate recenziile &rarr;
                    </Link>
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

        <section class="bg-slate-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="font-display text-3xl font-bold text-slate-900">Zone deservite</h2>
                    <p class="mx-auto mt-3 max-w-2xl text-slate-500">Intervenim in Bucuresti si in localitatile din jurul orasului, cu programare la ora stabilita.</p>
                </div>
                <ul class="mx-auto mt-10 flex max-w-3xl flex-wrap justify-center gap-3">
                    <li v-for="area in serviceAreas" :key="area" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm">
                        <Icon name="check" class="h-4 w-4 text-orange-500" />
                        {{ area }}
                    </li>
                </ul>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-brand-navy px-6 py-14 text-center text-white sm:px-10">
                <h2 class="font-display text-3xl font-bold sm:text-4xl">Ai nevoie de o reparatie sau un montaj?</h2>
                <p class="mx-auto mt-3 max-w-2xl text-slate-300">
                    Primesti constatare, deviz scris si programare clara inainte de inceperea lucrarii.
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-4">
                    <Link
                        :href="route('public.quote')"
                        class="rounded-md bg-orange-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-orange-500/25 transition hover:bg-orange-400"
                    >
                        Cere un deviz gratuit
                    </Link>
                    <a
                        v-if="phone"
                        :href="phoneHref"
                        class="inline-flex items-center gap-2 rounded-md border border-slate-600 px-6 py-3 text-sm font-semibold text-white transition hover:border-slate-400 hover:bg-white/5"
                    >
                        <Icon name="smartphone" class="h-4 w-4 text-orange-400" />
                        Suna acum
                    </a>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
